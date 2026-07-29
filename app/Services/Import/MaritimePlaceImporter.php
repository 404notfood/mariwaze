<?php

namespace App\Services\Import;

use App\Models\Anchorage;
use App\Models\Port;
use App\Support\Postgis;
use Illuminate\Support\Facades\DB;

/**
 * Traduit les éléments OpenStreetMap en ports et mouillages MariWaze.
 *
 * Tags utilisés :
 * - ports    : leisure=marina, harbour=yes, seamark:type=harbour
 * - mouillage: natural=bay, seamark:type=anchorage, waterway=anchorage
 */
class MaritimePlaceImporter
{
    /** @var array{south: float, west: float, north: float, east: float} Méditerranée occidentale + golfe de Gascogne */
    public const DEFAULT_BBOX = ['south' => 35.5, 'west' => -10.0, 'north' => 51.5, 'east' => 16.0];

    /**
     * Façades maritimes couvertes par défaut.
     *
     * Un grand rectangle englobe l'intérieur des terres et ramène des marinas
     * de lacs et de rivières ; ces zones suivent les côtes.
     *
     * @var array<string, array{south: float, west: float, north: float, east: float}>
     */
    public const SEA_AREAS = [
        'manche-mer-du-nord' => ['south' => 48.5, 'west' => -5.2, 'north' => 51.3, 'east' => 4.5],
        'atlantique-bretagne' => ['south' => 46.8, 'west' => -5.2, 'north' => 48.9, 'east' => -1.0],
        'atlantique-gascogne' => ['south' => 43.2, 'west' => -3.0, 'north' => 47.0, 'east' => -0.8],
        'mediterranee-francaise' => ['south' => 42.3, 'west' => 2.9, 'north' => 43.9, 'east' => 7.8],
        'corse' => ['south' => 41.3, 'west' => 8.4, 'north' => 43.1, 'east' => 9.7],
        'baleares' => ['south' => 38.5, 'west' => 1.1, 'north' => 40.2, 'east' => 4.4],
        'catalogne-valence' => ['south' => 37.8, 'west' => -0.9, 'north' => 42.5, 'east' => 3.4],
        'ligurie-toscane' => ['south' => 42.2, 'west' => 8.0, 'north' => 44.5, 'east' => 11.3],
        'sardaigne' => ['south' => 38.8, 'west' => 8.0, 'north' => 41.3, 'east' => 9.9],
    ];

    public function __construct(private readonly OverpassClient $overpass) {}

    /** Filtres OSM interrogés pour chaque tuile. */
    private const FILTERS = [
        'node["leisure"="marina"]',
        'way["leisure"="marina"]',
        'node["seamark:type"="harbour"]',
        'node["natural"="bay"]',
        'way["natural"="bay"]',
        'node["seamark:type"="anchorage"]',
        'way["seamark:type"="anchorage"]',
    ];

    /**
     * Découpe la zone en tuiles et importe chacune séparément.
     *
     * Overpass renvoie un 504 sur les zones trop vastes : le découpage garde
     * chaque requête dans les limites du service public.
     *
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @param  (callable(array{south: float, west: float, north: float, east: float}, array{ports: int, anchorages: int, skipped: int}): void)|null  $onTile
     * @return array{ports: int, anchorages: int, skipped: int, failed: int}
     */
    public function importArea(array $bbox, bool $replace = false, float $tileSize = 2.0, ?callable $onTile = null): array
    {
        if ($replace) {
            Port::query()->where('source', 'osm')->delete();
            Anchorage::query()->where('source', 'osm')->delete();
        }

        $totals = ['ports' => 0, 'anchorages' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($this->tiles($bbox, $tileSize) as $tile) {
            try {
                $stats = $this->import($tile);
            } catch (OverpassUnavailable) {
                $totals['failed']++;

                if ($onTile !== null) {
                    $onTile($tile, ['ports' => 0, 'anchorages' => 0, 'skipped' => 0]);
                }

                continue;
            }

            $totals['ports'] += $stats['ports'];
            $totals['anchorages'] += $stats['anchorages'];
            $totals['skipped'] += $stats['skipped'];

            if ($onTile !== null) {
                $onTile($tile, $stats);
            }
        }

        return $totals;
    }

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @return list<array{south: float, west: float, north: float, east: float}>
     */
    public function tiles(array $bbox, float $tileSize = 2.0): array
    {
        $tiles = [];

        for ($lat = $bbox['south']; $lat < $bbox['north']; $lat += $tileSize) {
            for ($lon = $bbox['west']; $lon < $bbox['east']; $lon += $tileSize) {
                $tiles[] = [
                    'south' => round($lat, 5),
                    'west' => round($lon, 5),
                    'north' => round(min($lat + $tileSize, $bbox['north']), 5),
                    'east' => round(min($lon + $tileSize, $bbox['east']), 5),
                ];
            }
        }

        return $tiles;
    }

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @return array{ports: int, anchorages: int, skipped: int}
     */
    public function import(array $bbox, bool $replace = false): array
    {
        $elements = $this->overpass->query($this->overpass->build(
            self::FILTERS,
            $bbox,
            (int) config('services.overpass.timeout'),
        ));

        $stats = ['ports' => 0, 'anchorages' => 0, 'skipped' => 0];

        DB::transaction(function () use ($elements, $replace, &$stats): void {
            if ($replace) {
                Port::query()->where('source', 'osm')->delete();
                Anchorage::query()->where('source', 'osm')->delete();
            }

            foreach ($elements as $element) {
                $point = $this->coordinates($element);
                $tags = $element['tags'] ?? [];
                $name = $tags['name'] ?? $tags['seamark:name'] ?? null;

                if ($point === null || ! is_string($name) || trim($name) === '') {
                    $stats['skipped']++;

                    continue;
                }

                $reference = ($element['type'] ?? 'node').'/'.($element['id'] ?? '');

                if ($this->isPort($tags)) {
                    $this->upsertPort($name, $point, $tags, $reference);
                    $stats['ports']++;

                    continue;
                }

                if ($this->isAnchorage($tags)) {
                    $this->upsertAnchorage($name, $point, $tags, $reference);
                    $stats['anchorages']++;

                    continue;
                }

                $stats['skipped']++;
            }

            Postgis::syncPointColumn('ports');
            Postgis::syncPointColumn('anchorages');
        });

        return $stats;
    }

    /** @param array<string, mixed> $tags */
    private function isPort(array $tags): bool
    {
        if ($this->isInland($tags)) {
            return false;
        }

        return ($tags['leisure'] ?? null) === 'marina'
            || ($tags['harbour'] ?? null) === 'yes'
            || ($tags['seamark:type'] ?? null) === 'harbour';
    }

    /**
     * Écarte les marinas fluviales et lacustres : MariWaze couvre la mer.
     *
     * OSM ne marque pas systématiquement l'eau salée ; on s'appuie sur les
     * tags explicites d'eau douce et sur l'absence de contexte maritime.
     *
     * @param  array<string, mixed>  $tags
     */
    private function isInland(array $tags): bool
    {
        if (($tags['seamark:type'] ?? null) !== null) {
            return false;
        }

        foreach (['water', 'waterway', 'natural'] as $key) {
            $value = $tags[$key] ?? null;

            if (is_string($value) && in_array($value, ['lake', 'river', 'canal', 'pond', 'reservoir', 'stream'], true)) {
                return true;
            }
        }

        return ($tags['harbour:category'] ?? null) === 'inland'
            || ($tags['seamark:harbour:category'] ?? null) === 'inland';
    }

    /** @param array<string, mixed> $tags */
    private function isAnchorage(array $tags): bool
    {
        return ($tags['natural'] ?? null) === 'bay'
            || ($tags['seamark:type'] ?? null) === 'anchorage'
            || ($tags['waterway'] ?? null) === 'anchorage';
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array{latitude: float, longitude: float}|null
     */
    private function coordinates(array $element): ?array
    {
        $latitude = $element['lat'] ?? $element['center']['lat'] ?? null;
        $longitude = $element['lon'] ?? $element['center']['lon'] ?? null;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        return ['latitude' => round((float) $latitude, 7), 'longitude' => round((float) $longitude, 7)];
    }

    /**
     * @param  array{latitude: float, longitude: float}  $point
     * @param  array<string, mixed>  $tags
     */
    private function upsertPort(string $name, array $point, array $tags, string $reference): void
    {
        Port::query()->updateOrCreate(
            ['source' => 'osm', 'source_ref' => $reference],
            [
                'slug' => $this->slug($name, $reference),
                'name' => $name,
                'region' => $tags['addr:city'] ?? $tags['addr:place'] ?? null,
                'country_code' => $this->countryCode($tags),
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'min_depth_meters' => $this->float($tags['seamark:harbour:depth'] ?? $tags['depth'] ?? null),
                'max_depth_meters' => $this->float($tags['maxdepth'] ?? null),
                'max_length_meters' => $this->float($tags['maxlength'] ?? null),
                'max_width_meters' => $this->float($tags['maxwidth'] ?? null),
                'berths' => $this->integer($tags['capacity'] ?? $tags['seamark:harbour:capacity'] ?? null),
                'has_fuel' => $this->flag($tags, ['fuel', 'seamark:small_craft_facility:category'], 'fuel'),
                'has_water' => $this->flag($tags, ['drinking_water', 'water_point'], 'water'),
                'has_electricity' => $this->flag($tags, ['power_supply', 'electricity'], 'electricity'),
                'has_shower' => $this->flag($tags, ['shower'], 'shower'),
                'has_repair' => $this->flag($tags, ['service:boat:repair', 'repair'], 'boatyard'),
                'has_provisions' => $this->flag($tags, ['shop'], 'provisions'),
                'vhf_channel' => $tags['seamark:radio_station:channel'] ?? $tags['vhf'] ?? null,
                'phone' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
                'website' => $tags['website'] ?? $tags['contact:website'] ?? null,
                'description' => $tags['description'] ?? null,
            ],
        );
    }

    /**
     * @param  array{latitude: float, longitude: float}  $point
     * @param  array<string, mixed>  $tags
     */
    private function upsertAnchorage(string $name, array $point, array $tags, string $reference): void
    {
        Anchorage::query()->updateOrCreate(
            ['source' => 'osm', 'source_ref' => $reference],
            [
                'slug' => $this->slug($name, $reference),
                'name' => $name,
                'region' => $tags['addr:city'] ?? $tags['addr:place'] ?? null,
                'country_code' => $this->countryCode($tags),
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'min_depth_meters' => $this->float($tags['seamark:anchorage:depth'] ?? $tags['depth'] ?? null),
                'max_depth_meters' => $this->float($tags['maxdepth'] ?? null),
                'bottom_type' => $tags['seamark:bottom:nature'] ?? $tags['surface'] ?? null,
                'holding' => $tags['seamark:anchorage:quality'] ?? null,
                'description' => $tags['description'] ?? null,
            ],
        );
    }

    /** @param array<string, mixed> $tags */
    private function countryCode(array $tags): ?string
    {
        $code = $tags['addr:country'] ?? $tags['is_in:country_code'] ?? null;

        return is_string($code) && strlen($code) === 2 ? strtoupper($code) : null;
    }

    /**
     * @param  array<string, mixed>  $tags
     * @param  list<string>  $keys
     */
    private function flag(array $tags, array $keys, string $needle): bool
    {
        foreach ($keys as $key) {
            $value = $tags[$key] ?? null;

            if ($value === 'yes' || $value === true) {
                return true;
            }

            if (is_string($value) && str_contains(strtolower($value), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function slug(string $name, string $reference): string
    {
        $base = str($name)->ascii()->lower()->replace(['’', "'"], '')->slug()->toString();
        $suffix = str($reference)->replace('/', '-')->toString();

        return $base === '' ? $suffix : $base.'-'.str($suffix)->afterLast('-');
    }
}
