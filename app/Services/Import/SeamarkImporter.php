<?php

namespace App\Services\Import;

use App\Models\Seamark;

/**
 * Importe le balisage OpenSeaMap/OSM : bouées et balises cardinales,
 * latérales, dangers isolés, eaux saines, marques spéciales, phares et feux.
 */
class SeamarkImporter
{
    /** Types seamark récoltés (nodes uniquement : le balisage est ponctuel). */
    private const SEAMARK_TYPES = [
        'buoy_cardinal', 'beacon_cardinal',
        'buoy_lateral', 'beacon_lateral',
        'buoy_isolated_danger', 'beacon_isolated_danger',
        'buoy_safe_water', 'beacon_safe_water',
        'buoy_special_purpose', 'beacon_special_purpose',
        'light_major', 'light_minor',
    ];

    public function __construct(private readonly OverpassClient $overpass) {}

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @param  (callable(array{south: float, west: float, north: float, east: float}, int): void)|null  $onTile
     * @return array{imported: int, skipped: int, failed: int}
     */
    public function importArea(array $bbox, bool $replace = false, float $tileSize = 2.0, ?callable $onTile = null): array
    {
        if ($replace) {
            Seamark::query()->delete();
        }

        $totals = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

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
        $filters = [
            ...array_map(
                fn (string $type): string => sprintf('node["seamark:type"="%s"]', $type),
                self::SEAMARK_TYPES,
            ),
            'node["man_made"="lighthouse"]',
        ];

        $imported = 0;

        foreach ($this->overpass->query($this->overpass->build($filters, $tile)) as $element) {
            $latitude = $element['lat'] ?? null;
            $longitude = $element['lon'] ?? null;

            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                continue;
            }

            $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
            $category = $this->category($tags);

            if ($category === null) {
                continue;
            }

            Seamark::updateOrCreate(
                ['source_ref' => ($element['type'] ?? 'node').'/'.($element['id'] ?? '0')],
                [
                    'name' => $tags['seamark:name'] ?? $tags['name'] ?? null,
                    'category' => $category,
                    'seamark_type' => $tags['seamark:type'] ?? 'lighthouse',
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                ],
            );

            $imported++;
        }

        return $imported;
    }

    /**
     * Catégorie normalisée à partir des tags OSM, ou null si inexploitable.
     *
     * @param  array<string, mixed>  $tags
     */
    private function category(array $tags): ?string
    {
        if (($tags['man_made'] ?? null) === 'lighthouse') {
            return 'lighthouse';
        }

        $type = $tags['seamark:type'] ?? '';

        return match (true) {
            str_ends_with($type, '_cardinal') => match ($tags["seamark:{$type}:category"] ?? null) {
                'north' => 'cardinal_north',
                'south' => 'cardinal_south',
                'east' => 'cardinal_east',
                'west' => 'cardinal_west',
                default => null,
            },
            str_ends_with($type, '_lateral') => match ($tags["seamark:{$type}:category"] ?? null) {
                'port' => 'lateral_port',
                'starboard' => 'lateral_starboard',
                default => null,
            },
            str_ends_with($type, '_isolated_danger') => 'isolated_danger',
            str_ends_with($type, '_safe_water') => 'safe_water',
            str_ends_with($type, '_special_purpose') => 'special_purpose',
            $type === 'light_major' => 'lighthouse',
            $type === 'light_minor' => 'light',
            default => null,
        };
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
