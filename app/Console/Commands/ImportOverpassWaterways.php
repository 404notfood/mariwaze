<?php

namespace App\Console\Commands;

use App\Models\WaterwayEdge;
use App\Models\WaterwayNode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOverpassWaterways extends Command
{
    protected $signature = 'waterways:import-overpass {path : Fichier JSON produit par Overpass} {--replace : Remplace le reseau fluvial local existant}';

    protected $description = 'Importe les voies navigables OSM depuis une reponse Overpass contenant les geometries des ways.';

    public function handle(): int
    {
        $path = $this->input()->string('path')->toString();
        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $ways = $this->decodeWays((string) file_get_contents($path));

        if ($ways === []) {
            $this->error('Aucune voie avec geometrie dans ce fichier.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($ways): void {
            if ($this->option('replace')) {
                WaterwayEdge::query()->delete();
                WaterwayNode::query()->delete();
            }

            $nodes = 0;
            $edges = 0;
            foreach ($ways as $way) {
                $coordinates = $way['geometry'];
                $tags = $way['tags'];

                for ($index = 1; $index < count($coordinates); $index++) {
                    $fromPoint = $coordinates[$index - 1];
                    $toPoint = $coordinates[$index];
                    $from = WaterwayNode::query()->firstOrCreate($fromPoint);
                    $to = WaterwayNode::query()->firstOrCreate($toPoint);
                    $nodes += $from->wasRecentlyCreated ? 1 : 0;
                    $nodes += $to->wasRecentlyCreated ? 1 : 0;

                    $attributes = [
                        'name' => $tags['name'] ?? null,
                        'distance_meters' => $this->meters($fromPoint, $toPoint),
                        'lock_count' => 0,
                    ];
                    WaterwayEdge::query()->firstOrCreate(['from_node_id' => $from->id, 'to_node_id' => $to->id], $attributes);
                    $edges++;

                    if (($tags['oneway'] ?? null) !== 'yes') {
                        WaterwayEdge::query()->firstOrCreate(['from_node_id' => $to->id, 'to_node_id' => $from->id], $attributes);
                        $edges++;
                    }
                }
            }

            $this->info("Import termine : {$nodes} noeuds ajoutes, {$edges} arcs analyses.");
        });

        return self::SUCCESS;
    }

    /**
     * @return list<array{geometry: list<array{latitude: float, longitude: float}>, tags: array<string, mixed>}>
     */
    private function decodeWays(string $json): array
    {
        $document = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($document) || ! is_array($document['elements'] ?? null)) {
            return [];
        }

        $ways = [];

        foreach ($document['elements'] as $element) {
            if (! is_array($element) || ($element['type'] ?? null) !== 'way' || ! is_array($element['geometry'] ?? null)) {
                continue;
            }

            $geometry = [];

            foreach ($element['geometry'] as $point) {
                if (! is_array($point) || ! is_numeric($point['lat'] ?? null) || ! is_numeric($point['lon'] ?? null)) {
                    continue;
                }

                $geometry[] = [
                    'latitude' => round((float) $point['lat'], 7),
                    'longitude' => round((float) $point['lon'], 7),
                ];
            }

            if (count($geometry) < 2) {
                continue;
            }

            $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
            $ways[] = ['geometry' => $geometry, 'tags' => $tags];
        }

        return $ways;
    }

    /**
     * @param  array{latitude: float, longitude: float}  $from
     * @param  array{latitude: float, longitude: float}  $to
     */
    private function meters(array $from, array $to): float
    {
        $latitude = deg2rad($to['latitude'] - $from['latitude']);
        $longitude = deg2rad($to['longitude'] - $from['longitude']);
        $a = sin($latitude / 2) ** 2 + cos(deg2rad($from['latitude'])) * cos(deg2rad($to['latitude'])) * sin($longitude / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
