<?php

namespace App\Services\Import;

use App\Models\HazardZone;
use App\Support\GeoScope;
use Illuminate\Support\Str;

/**
 * Importe les zones réglementées réelles d'OSM avec leur contour exact :
 * dispositifs de séparation du trafic (DST) et zones militaires.
 *
 * Ces polygones remplacent avantageusement les zones circulaires du seed pour
 * l'alerte et l'affichage — les zones de seed importées deviennent redondantes
 * quand un polygone OSM couvre le même secteur, mais les deux coexistent sans
 * dommage (mêmes catégories, alertes dédupliquées par zone).
 */
class HazardAreaImporter
{
    /** Emprise par défaut : Europe de l'Ouest, Manche → Gibraltar → Méditerranée occidentale. */
    public const DEFAULT_BBOX = ['south' => 34.5, 'west' => -12.0, 'north' => 52.5, 'east' => 12.0];

    public function __construct(private readonly OverpassClient $overpass) {}

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @param  (callable(array{south: float, west: float, north: float, east: float}, int): void)|null  $onTile
     * @return array{imported: int, failed: int}
     */
    public function importArea(array $bbox, bool $replace = false, float $tileSize = 4.0, ?callable $onTile = null): array
    {
        if ($replace) {
            HazardZone::query()->whereNotNull('source_ref')->delete();
        }

        $totals = ['imported' => 0, 'failed' => 0];

        foreach ($this->tiles($bbox, $tileSize) as $tile) {
            try {
                $imported = $this->importTile($tile);
                $totals['imported'] += $imported;
            } catch (OverpassUnavailable) {
                $totals['failed']++;
                $imported = 0;
            }

            if ($onTile !== null) {
                $onTile($tile, $imported);
            }
        }

        return $totals;
    }

    /** @param array{south: float, west: float, north: float, east: float} $tile */
    private function importTile(array $tile): int
    {
        $box = sprintf('%F,%F,%F,%F', $tile['south'], $tile['west'], $tile['north'], $tile['east']);
        $query = <<<QL
        [out:json][timeout:120];
        (
          way["seamark:type"~"^(separation_zone|separation_lane|separation_roundabout|inshore_traffic_zone)$"]({$box});
          way["seamark:type"="restricted_area"]["seamark:restricted_area:category"~"military|firing|exercise"]({$box});
          relation["seamark:type"="restricted_area"]["seamark:restricted_area:category"~"military|firing|exercise"]({$box});
        );
        out geom tags;
        QL;

        $imported = 0;

        foreach ($this->overpass->query($query) as $element) {
            if ($this->importElement($element)) {
                $imported++;
            }
        }

        return $imported;
    }

    /** @param array<string, mixed> $element */
    private function importElement(array $element): bool
    {
        $geometry = $this->geometry($element);

        if (count($geometry) < 3) {
            return false;
        }

        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $type = $tags['seamark:type'] ?? '';
        $profile = $this->profile($type, $tags);

        if ($profile === null) {
            return false;
        }

        [$latitude, $longitude, $radius] = $this->enclosingCircle($geometry);
        $name = $tags['seamark:name'] ?? $tags['name'] ?? $profile['fallback_name'];
        $sourceRef = ($element['type'] ?? 'way').'/'.($element['id'] ?? '0');

        HazardZone::updateOrCreate(
            ['source_ref' => $sourceRef],
            [
                'slug' => 'osm-'.Str::slug(str_replace('/', '-', $sourceRef)),
                'name' => $name,
                'category' => $profile['category'],
                'severity' => $profile['severity'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_nm' => max(0.5, $radius),
                'description' => $profile['description'],
                'advice' => $profile['advice'],
                'months' => null,
                'source' => 'OpenStreetMap / OpenSeaMap',
                'geometry' => $geometry,
            ],
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $tags
     * @return array{category: string, severity: string, description: string, advice: string, fallback_name: string}|null
     */
    private function profile(string $type, array $tags): ?array
    {
        if ($type === 'restricted_area') {
            $category = $tags['seamark:restricted_area:category'] ?? '';

            return [
                'category' => 'military',
                'severity' => str_contains($category, 'firing') || str_contains($category, 'exercise') ? 'danger' : 'warning',
                'description' => 'Zone réglementée militaire (contour officiel OpenSeaMap) : accès restreint ou interdit par créneaux.',
                'advice' => 'Consulter les AVURNAV de la préfecture maritime compétente avant de traverser la zone.',
                'fallback_name' => 'Zone militaire réglementée',
            ];
        }

        return match ($type) {
            'separation_zone', 'separation_roundabout' => [
                'category' => 'traffic',
                'severity' => 'danger',
                'description' => "Zone de séparation d'un dispositif de trafic (DST) : pénétration interdite à la navigation (règle 10 du RIPAM).",
                'advice' => 'Contourner la zone ou traverser le dispositif perpendiculairement aux voies.',
                'fallback_name' => 'Zone de séparation de trafic',
            ],
            'separation_lane' => [
                'category' => 'traffic',
                'severity' => 'warning',
                'description' => "Voie de circulation d'un dispositif de séparation du trafic : navires de commerce en flux continu.",
                'advice' => 'Traverser perpendiculairement à la voie, veille VHF 16 et écoute du VTS local.',
                'fallback_name' => 'Voie de séparation de trafic',
            ],
            'inshore_traffic_zone' => [
                'category' => 'traffic',
                'severity' => 'info',
                'description' => 'Zone côtière de navigation associée à un DST, ouverte à la plaisance.',
                'advice' => 'Zone recommandée pour les navires de moins de 20 m plutôt que les voies du dispositif.',
                'fallback_name' => 'Zone côtière de navigation',
            ],
            default => null,
        };
    }

    /**
     * Contour de l'élément : way → geometry directe, relation → premier
     * anneau extérieur reconstitué à partir des membres.
     *
     * @param  array<string, mixed>  $element
     * @return list<array{latitude: float, longitude: float}>
     */
    private function geometry(array $element): array
    {
        $raw = [];

        if (is_array($element['geometry'] ?? null)) {
            $raw = $element['geometry'];
        } elseif (is_array($element['members'] ?? null)) {
            foreach ($element['members'] as $member) {
                if (($member['role'] ?? 'outer') === 'outer' && is_array($member['geometry'] ?? null)) {
                    $raw = [...$raw, ...$member['geometry']];
                }
            }
        }

        $points = [];

        foreach ($raw as $point) {
            if (is_array($point) && is_numeric($point['lat'] ?? null) && is_numeric($point['lon'] ?? null)) {
                $points[] = ['latitude' => (float) $point['lat'], 'longitude' => (float) $point['lon']];
            }
        }

        // Simplification grossière : 80 sommets suffisent pour l'alerte et la carte.
        $count = count($points);

        if ($count > 80) {
            $step = (int) ceil($count / 80);
            $points = array_values(array_filter($points, fn (int $index): bool => $index % $step === 0, ARRAY_FILTER_USE_KEY));
        }

        return $points;
    }

    /**
     * Centre et rayon englobant, pour le pré-filtre circulaire des alertes.
     *
     * @param  non-empty-list<array{latitude: float, longitude: float}>  $geometry
     * @return array{float, float, float}
     */
    private function enclosingCircle(array $geometry): array
    {
        $latitude = array_sum(array_column($geometry, 'latitude')) / count($geometry);
        $longitude = array_sum(array_column($geometry, 'longitude')) / count($geometry);
        $radius = 0.0;

        foreach ($geometry as $point) {
            $radius = max($radius, GeoScope::nauticalMiles($latitude, $longitude, $point['latitude'], $point['longitude']));
        }

        return [$latitude, $longitude, $radius];
    }

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @return list<array{south: float, west: float, north: float, east: float}>
     */
    private function tiles(array $bbox, float $tileSize): array
    {
        $tiles = [];

        for ($south = $bbox['south']; $south < $bbox['north']; $south += $tileSize) {
            for ($west = $bbox['west']; $west < $bbox['east']; $west += $tileSize) {
                $tiles[] = [
                    'south' => $south,
                    'west' => $west,
                    'north' => min($bbox['north'], $south + $tileSize),
                    'east' => min($bbox['east'], $west + $tileSize),
                ];
            }
        }

        return $tiles;
    }
}
