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
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $document = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $ways = collect($document['elements'] ?? [])
            ->filter(fn (mixed $element): bool => is_array($element) && ($element['type'] ?? null) === 'way' && count($element['geometry'] ?? []) > 1);

        if ($ways->isEmpty()) {
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
                $coordinates = collect($way['geometry'])->map(fn (array $point): array => [
                    'latitude' => round((float) $point['lat'], 7),
                    'longitude' => round((float) $point['lon'], 7),
                ]);
                $tags = $way['tags'] ?? [];

                foreach ($coordinates->sliding(2) as $segment) {
                    [$fromPoint, $toPoint] = $segment->all();
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

    /** @param array{latitude: float, longitude: float} $from @param array{latitude: float, longitude: float} $to */
    private function meters(array $from, array $to): float
    {
        $latitude = deg2rad($to['latitude'] - $from['latitude']);
        $longitude = deg2rad($to['longitude'] - $from['longitude']);
        $a = sin($latitude / 2) ** 2 + cos(deg2rad($from['latitude'])) * cos(deg2rad($to['latitude'])) * sin($longitude / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
