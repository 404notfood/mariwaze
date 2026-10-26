"""Routage maritime pour MariWaze.

Deux moteurs complémentaires :

- ``searoute`` (réseau mondial de voies maritimes, grossier) : ossature des
  longues traversées et repli systématique ;
- un A* sur le masque terre/mer ``global-land-mask`` (~1 km de résolution),
  utilisé pour les étapes côtières : le tracé contourne les caps et les îles,
  emprunte les chenaux (dès ~1 km de large) et garde une marge de garde avec
  la côte quand c'est possible.

Entrée (stdin, JSON) :
- ``{"origin": {...}, "destination": {...}}`` → liste de coordonnées [lon, lat]
- ``{"waypoints": [{...}, ...]}`` → ``{"legs": [[[lon, lat], ...], ...]}``
"""

import heapq
import json
import math
import sys

import searoute as sr

try:
    import numpy as np
    from global_land_mask import globe

    HAS_COASTAL = True
except ImportError:  # Paquets optionnels : on retombe sur searoute seul.
    HAS_COASTAL = False

# Au-delà de cette distance, une étape est considérée hauturière : le réseau
# grossier suffit et le corridor A* serait démesuré.
MAX_FINE_LEG_NM = 250.0
NM_PER_DEG = 60.0
# Taille maximale de la grille A* (nombre de cellules).
MAX_GRID_CELLS = 700_000
# Pénalité de marche pour une cellule d'eau collée à la terre : le tracé
# préfère passer au large quand un couloir existe.
NEAR_LAND_PENALTY = 0.8


def haversine_nm(a_lat, a_lon, b_lat, b_lon):
    lat = math.radians(b_lat - a_lat)
    lon = math.radians(b_lon - a_lon)
    h = math.sin(lat / 2) ** 2 + math.cos(math.radians(a_lat)) * math.cos(math.radians(b_lat)) * math.sin(lon / 2) ** 2
    h = min(1.0, max(0.0, h))
    return 3440.065 * 2 * math.atan2(math.sqrt(h), math.sqrt(1 - h))


def searoute_leg(origin, destination):
    """Route du réseau maritime mondial, en coordonnées [lon, lat]."""
    route = sr.searoute(
        [origin["longitude"], origin["latitude"]],
        [destination["longitude"], destination["latitude"]],
    )
    return route["geometry"]["coordinates"]


def build_grid(origin, destination, margin_factor):
    """Grille du corridor entre les deux points : eau, proximité de terre."""
    lat_min, lat_max = sorted((origin["latitude"], destination["latitude"]))
    destination_lon = origin["longitude"] + (destination["longitude"] - origin["longitude"] + 180) % 360 - 180
    lon_min, lon_max = sorted((origin["longitude"], destination_lon))
    span = max(lat_max - lat_min, lon_max - lon_min)
    margin = max(0.35, span * margin_factor)
    lat_min -= margin
    lat_max += margin
    lon_min -= margin
    lon_max += margin

    cell = max(0.008, span / 400.0)
    rows = int((lat_max - lat_min) / cell) + 1
    cols = int((lon_max - lon_min) / cell) + 1

    while rows * cols > MAX_GRID_CELLS:
        cell *= 1.4
        rows = int((lat_max - lat_min) / cell) + 1
        cols = int((lon_max - lon_min) / cell) + 1

    lats = lat_min + np.arange(rows) * cell
    lons = lon_min + np.arange(cols) * cell
    lon_grid, lat_grid = np.meshgrid(lons, lats)
    land = globe.is_land(np.clip(lat_grid, -89.9, 89.9), (lon_grid + 180) % 360 - 180)

    # Cellules d'eau adjacentes à la terre (dilatation 8-connexe, 3 passes).
    near = land.copy()
    for _ in range(3):
        grown = near.copy()
        grown[1:, :] |= near[:-1, :]
        grown[:-1, :] |= near[1:, :]
        grown[:, 1:] |= near[:, :-1]
        grown[:, :-1] |= near[:, 1:]
        near = grown
    near_land = near & ~land

    return {
        "lat_min": lat_min,
        "lon_min": lon_min,
        "cell": cell,
        "rows": rows,
        "cols": cols,
        "land": land,
        "near_land": near_land,
        "kx": max(0.05, math.cos(math.radians((lat_min + lat_max) / 2))),
    }


def to_cell(grid, point):
    row = int(round((point["latitude"] - grid["lat_min"]) / grid["cell"]))
    center_lon = grid["lon_min"] + grid["cols"] * grid["cell"] / 2
    longitude = point["longitude"] + 360 * round((center_lon - point["longitude"]) / 360)
    col = int(round((longitude - grid["lon_min"]) / grid["cell"]))
    return (min(max(row, 0), grid["rows"] - 1), min(max(col, 0), grid["cols"] - 1))


def snap_to_water(grid, cell_pos, radius=12):
    """Cellule d'eau la plus proche : les marinas sont souvent « à terre »."""
    if not grid["land"][cell_pos]:
        return cell_pos
    row, col = cell_pos
    best = None
    best_distance = None
    for dr in range(-radius, radius + 1):
        for dc in range(-radius, radius + 1):
            r, c = row + dr, col + dc
            if 0 <= r < grid["rows"] and 0 <= c < grid["cols"] and not grid["land"][r, c]:
                distance = dr * dr + dc * dc
                if best_distance is None or distance < best_distance:
                    best, best_distance = (r, c), distance
    return best


def a_star(grid, start, goal):
    """Plus court chemin en eau, pénalisant les cellules collées à la côte."""
    kx = grid["kx"]
    land = grid["land"]
    near_land = grid["near_land"]
    rows, cols = grid["rows"], grid["cols"]
    neighbors = [
        (-1, 0, 1.0), (1, 0, 1.0), (0, -1, kx), (0, 1, kx),
        (-1, -1, math.hypot(1, kx)), (-1, 1, math.hypot(1, kx)),
        (1, -1, math.hypot(1, kx)), (1, 1, math.hypot(1, kx)),
    ]

    def heuristic(pos):
        return 1.25 * math.hypot(pos[0] - goal[0], (pos[1] - goal[1]) * kx)

    queue = [(heuristic(start), 0.0, start)]
    best_cost = {start: 0.0}
    came_from = {}

    while queue:
        _, cost, current = heapq.heappop(queue)
        if current == goal:
            path = [current]
            while current in came_from:
                current = came_from[current]
                path.append(current)
            return path[::-1]
        if cost > best_cost.get(current, math.inf):
            continue
        for dr, dc, step in neighbors:
            r, c = current[0] + dr, current[1] + dc
            if not (0 <= r < rows and 0 <= c < cols) or land[r, c]:
                continue
            candidate = cost + step + (NEAR_LAND_PENALTY * step if near_land[r, c] else 0.0)
            if candidate < best_cost.get((r, c), math.inf):
                best_cost[(r, c)] = candidate
                came_from[(r, c)] = current
                heapq.heappush(queue, (candidate + heuristic((r, c)), candidate, (r, c)))

    return None


def simplify(path, tolerance):
    """Douglas-Peucker sur les indices de grille (itératif)."""
    if len(path) < 3:
        return path
    keep = [False] * len(path)
    keep[0] = keep[-1] = True
    stack = [(0, len(path) - 1)]
    while stack:
        first, last = stack.pop()
        a, b = path[first], path[last]
        farthest, farthest_distance = None, tolerance
        for i in range(first + 1, last):
            p = path[i]
            dx, dy = b[0] - a[0], b[1] - a[1]
            length = math.hypot(dx, dy)
            if length == 0:
                distance = math.hypot(p[0] - a[0], p[1] - a[1])
            else:
                distance = abs(dx * (a[1] - p[1]) - (a[0] - p[0]) * dy) / length
            if distance > farthest_distance:
                farthest, farthest_distance = i, distance
        if farthest is not None:
            keep[farthest] = True
            stack.append((first, farthest))
            stack.append((farthest, last))
    return [p for i, p in enumerate(path) if keep[i]]


def crosses_land(grid, a, b):
    """La ligne droite entre deux cellules passe-t-elle sur la terre ?"""
    steps = max(2, int(2 * math.hypot(b[0] - a[0], b[1] - a[1])))
    for s in range(1, steps):
        t = s / steps
        r = int(round(a[0] + (b[0] - a[0]) * t))
        c = int(round(a[1] + (b[1] - a[1]) * t))
        if 0 <= r < grid["rows"] and 0 <= c < grid["cols"] and grid["land"][r, c]:
            return True
    return False


def safe_simplify(grid, path, tolerance):
    """Douglas-Peucker, mais aucun raccourci ne doit couper la terre."""
    simplified = simplify(path, tolerance)
    index_of = {point: i for i, point in enumerate(path)}
    result = [simplified[0]]
    for i in range(1, len(simplified)):
        a, b = simplified[i - 1], simplified[i]
        if crosses_land(grid, a, b):
            # On réinjecte le tracé original entre les deux sommets.
            result.extend(path[index_of[a] + 1:index_of[b] + 1])
        else:
            result.append(b)
    return result


def coastal_leg(origin, destination):
    """Tracé côtier fin, ou None si le corridor ne mène pas au but."""
    for margin_factor in (0.45, 1.0):
        grid = build_grid(origin, destination, margin_factor)
        start = snap_to_water(grid, to_cell(grid, origin))
        goal = snap_to_water(grid, to_cell(grid, destination))
        if start is None or goal is None:
            continue
        path = a_star(grid, start, goal)
        if path is None:
            continue
        path = safe_simplify(grid, path, tolerance=1.2)
        coordinates = [
            [(grid["lon_min"] + c * grid["cell"] + 180) % 360 - 180, grid["lat_min"] + r * grid["cell"]]
            for r, c in path
        ]
        # Extrémités exactes (le PHP raccorde aussi, ceinture et bretelles).
        coordinates[0] = [origin["longitude"], origin["latitude"]]
        coordinates[-1] = [destination["longitude"], destination["latitude"]]
        return coordinates
    return None


def leg(origin, destination):
    distance = haversine_nm(
        origin["latitude"], origin["longitude"],
        destination["latitude"], destination["longitude"],
    )
    if HAS_COASTAL and distance <= MAX_FINE_LEG_NM:
        try:
            fine = coastal_leg(origin, destination)
            if fine is not None:
                return fine
        except Exception:
            pass
    return searoute_leg(origin, destination)


def main():
    payload = json.load(sys.stdin)

    if "waypoints" in payload:
        waypoints = payload["waypoints"]
        legs = [leg(waypoints[i - 1], waypoints[i]) for i in range(1, len(waypoints))]
        print(json.dumps({"legs": legs}))
        return

    print(json.dumps(leg(payload["origin"], payload["destination"])))


if __name__ == "__main__":
    main()
