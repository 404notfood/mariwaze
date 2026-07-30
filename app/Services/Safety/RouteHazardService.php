<?php

namespace App\Services\Safety;

use App\Models\HazardZone;
use App\Support\GeoScope;
use Carbon\CarbonInterface;

/**
 * Croise le tracé d'une croisière avec les zones de vigilance connues
 * (militaires, orques, piraterie, trafic, aires protégées).
 *
 * Une zone est signalée quand le tracé passe à l'intérieur de son rayon,
 * ou à moins de NEARBY_MARGIN_NM de sa limite (« à proximité »).
 */
class RouteHazardService
{
    private const NEARBY_MARGIN_NM = 15.0;

    /**
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @return list<array<string, mixed>>
     */
    public function alertsForGeometry(array $geometry, ?CarbonInterface $departure = null, int $durationDays = 0): array
    {
        if (count($geometry) < 2) {
            return [];
        }

        $months = $this->cruiseMonths($departure, $durationDays);
        $alerts = [];

        foreach (HazardZone::query()->get() as $zone) {
            // Pré-filtre par cercle englobant : bon marché, écarte l'immense
            // majorité des zones avant le test polygone précis.
            $distance = $this->distanceToPath($zone->latitude, $zone->longitude, $geometry);

            if ($distance > $zone->radius_nm + self::NEARBY_MARGIN_NM) {
                continue;
            }

            $polygon = is_array($zone->geometry) && count($zone->geometry) >= 3 ? $zone->geometry : null;
            $onRoute = $polygon === null
                ? $distance <= $zone->radius_nm
                : $this->pathTouchesPolygon($geometry, $polygon);
            $edgeDistance = $polygon === null
                ? max(0, $distance - $zone->radius_nm)
                : ($onRoute ? 0.0 : $this->pathToPolygonDistance($geometry, $polygon));

            if (! $onRoute && $edgeDistance > self::NEARBY_MARGIN_NM) {
                continue;
            }

            $alerts[] = [
                'slug' => $zone->slug,
                'name' => $zone->name,
                'category' => $zone->category,
                'category_label' => $zone->categoryLabel(),
                'severity' => $zone->severity,
                'description' => $zone->description,
                'advice' => $zone->advice,
                'latitude' => $zone->latitude,
                'longitude' => $zone->longitude,
                'radius_nm' => $zone->radius_nm,
                'geometry' => $polygon,
                'on_route' => $onRoute,
                'distance_nm' => round($edgeDistance),
                'in_season' => $zone->months === null || $months === [] || array_intersect($zone->months, $months) !== [],
                'months' => $zone->months,
                'source' => $zone->source,
            ];
        }

        usort($alerts, function (array $a, array $b): int {
            $rank = ['danger' => 0, 'warning' => 1, 'info' => 2];

            return [$rank[$a['severity']] ?? 3, $a['distance_nm']] <=> [$rank[$b['severity']] ?? 3, $b['distance_nm']];
        });

        return $alerts;
    }

    /**
     * Mois calendaires couverts par la croisière (1-12).
     *
     * @return list<int>
     */
    private function cruiseMonths(?CarbonInterface $departure, int $durationDays): array
    {
        if ($departure === null) {
            return [];
        }

        $months = [];
        // avoidMutation() : Carbon comme CarbonImmutable renvoient une copie.
        $cursor = $departure->avoidMutation()->startOfMonth();
        $end = $departure->avoidMutation()->addDays(max(0, $durationDays));

        while ($cursor->lessThanOrEqualTo($end) && count($months) < 12) {
            $months[] = $cursor->month;
            $cursor = $cursor->avoidMutation()->addMonth();
        }

        return $months;
    }

    /**
     * Le tracé touche-t-il le polygone (sommet à l'intérieur ou arêtes sécantes) ?
     *
     * @param  list<array{latitude: float, longitude: float}>  $path
     * @param  array<int, array{latitude: float, longitude: float}>  $polygon
     */
    private function pathTouchesPolygon(array $path, array $polygon): bool
    {
        foreach ($path as $point) {
            if ($this->pointInPolygon($point['latitude'], $point['longitude'], $polygon)) {
                return true;
            }
        }

        $edges = count($polygon);

        for ($i = 1; $i < count($path); $i++) {
            for ($j = 0; $j < $edges; $j++) {
                $next = ($j + 1) % $edges;

                if ($this->segmentsIntersect($path[$i - 1], $path[$i], $polygon[$j], $polygon[$next])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Distance minimale (milles nautiques) entre le tracé et le bord du polygone.
     *
     * @param  list<array{latitude: float, longitude: float}>  $path
     * @param  array<int, array{latitude: float, longitude: float}>  $polygon
     */
    private function pathToPolygonDistance(array $path, array $polygon): float
    {
        $minimum = INF;
        $edges = count($polygon);

        foreach ($path as $point) {
            for ($j = 0; $j < $edges; $j++) {
                $next = ($j + 1) % $edges;
                $minimum = min($minimum, $this->distanceToSegment(
                    $point['latitude'],
                    $point['longitude'],
                    $polygon[$j]['latitude'],
                    $polygon[$j]['longitude'],
                    $polygon[$next]['latitude'],
                    $polygon[$next]['longitude'],
                ));
            }
        }

        foreach ($polygon as $vertex) {
            $minimum = min($minimum, $this->distanceToPath($vertex['latitude'], $vertex['longitude'], $path));
        }

        return $minimum;
    }

    /**
     * Test point-dans-polygone par lancer de rayon.
     *
     * @param  array<int, array{latitude: float, longitude: float}>  $polygon
     */
    private function pointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $a = $polygon[$i];
            $b = $polygon[$j];

            if (($a['latitude'] > $latitude) !== ($b['latitude'] > $latitude)
                && $longitude < ($b['longitude'] - $a['longitude']) * ($latitude - $a['latitude']) / ($b['latitude'] - $a['latitude']) + $a['longitude']) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Intersection de deux segments (produit vectoriel, invariant d'échelle).
     *
     * @param  array{latitude: float, longitude: float}  $a
     * @param  array{latitude: float, longitude: float}  $b
     * @param  array{latitude: float, longitude: float}  $c
     * @param  array{latitude: float, longitude: float}  $d
     */
    private function segmentsIntersect(array $a, array $b, array $c, array $d): bool
    {
        $cross = fn (array $o, array $p, array $q): float => ($p['longitude'] - $o['longitude']) * ($q['latitude'] - $o['latitude'])
            - ($p['latitude'] - $o['latitude']) * ($q['longitude'] - $o['longitude']);

        $d1 = $cross($c, $d, $a);
        $d2 = $cross($c, $d, $b);
        $d3 = $cross($a, $b, $c);
        $d4 = $cross($a, $b, $d);

        return (($d1 > 0) !== ($d2 > 0)) && (($d3 > 0) !== ($d4 > 0));
    }

    /**
     * Distance minimale (milles nautiques) entre un point et une polyligne.
     *
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     */
    private function distanceToPath(float $latitude, float $longitude, array $geometry): float
    {
        $minimum = INF;

        for ($i = 1; $i < count($geometry); $i++) {
            $minimum = min($minimum, $this->distanceToSegment(
                $latitude,
                $longitude,
                $geometry[$i - 1]['latitude'],
                $geometry[$i - 1]['longitude'],
                $geometry[$i]['latitude'],
                $geometry[$i]['longitude'],
            ));
        }

        return $minimum;
    }

    /**
     * Distance point-segment sur projection locale équirectangulaire : précision
     * largement suffisante à l'échelle des rayons de zone (quelques dizaines de
     * milles).
     */
    private function distanceToSegment(float $pLat, float $pLon, float $aLat, float $aLon, float $bLat, float $bLon): float
    {
        $cosLat = cos(deg2rad($pLat));

        // Coordonnées en milles nautiques dans le repère local du point.
        $ax = ($aLon - $pLon) * 60 * $cosLat;
        $ay = ($aLat - $pLat) * 60;
        $bx = ($bLon - $pLon) * 60 * $cosLat;
        $by = ($bLat - $pLat) * 60;

        $dx = $bx - $ax;
        $dy = $by - $ay;
        $lengthSquared = $dx * $dx + $dy * $dy;

        if ($lengthSquared < 1e-9) {
            return GeoScope::nauticalMiles($pLat, $pLon, $aLat, $aLon);
        }

        $t = max(0.0, min(1.0, -($ax * $dx + $ay * $dy) / $lengthSquared));
        $closestX = $ax + $t * $dx;
        $closestY = $ay + $t * $dy;

        return sqrt($closestX * $closestX + $closestY * $closestY);
    }
}
