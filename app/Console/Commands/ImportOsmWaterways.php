<?php

namespace App\Console\Commands;

use App\Models\WaterwayEdge;
use App\Models\WaterwayNode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use XMLReader;

class ImportOsmWaterways extends Command
{
    protected $signature = 'waterways:import-osm {path : Fichier XML OSM obtenu via l API map} {--replace : Remplace le reseau fluvial local existant}';

    protected $description = 'Importe les cours d eau et canaux OSM depuis un extrait XML.';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $nodes = [];
        $ways = [];
        $reader = new XMLReader;
        $reader->open($path);

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || ! in_array($reader->name, ['node', 'way'], true)) {
                continue;
            }

            if ($reader->name === 'node') {
                $nodes[(int) $reader->getAttribute('id')] = [
                    'latitude' => round((float) $reader->getAttribute('lat'), 7),
                    'longitude' => round((float) $reader->getAttribute('lon'), 7),
                ];

                continue;
            }

            $xml = simplexml_load_string($reader->readOuterXML());
            $tags = [];
            foreach ($xml->tag as $tag) {
                $tags[(string) $tag['k']] = (string) $tag['v'];
            }
            if (! in_array($tags['waterway'] ?? null, ['river', 'canal'], true)) {
                continue;
            }

            $ways[] = ['node_ids' => array_map(fn ($node): int => (int) $node['ref'], iterator_to_array($xml->nd)), 'tags' => $tags];
        }
        $reader->close();

        DB::transaction(function () use ($nodes, $ways): void {
            if ($this->option('replace')) {
                WaterwayEdge::query()->delete();
                WaterwayNode::query()->delete();
            }

            $localNodes = 0;
            $localEdges = 0;
            foreach ($ways as $way) {
                foreach (array_chunk($way['node_ids'], 2, true) as $pair) {
                    $pair = array_values($pair);
                    if (count($pair) !== 2 || ! isset($nodes[$pair[0]], $nodes[$pair[1]])) {
                        continue;
                    }
                    $from = WaterwayNode::query()->firstOrCreate($nodes[$pair[0]]);
                    $to = WaterwayNode::query()->firstOrCreate($nodes[$pair[1]]);
                    $localNodes += $from->wasRecentlyCreated ? 1 : 0;
                    $localNodes += $to->wasRecentlyCreated ? 1 : 0;
                    $attributes = ['name' => $way['tags']['name'] ?? null, 'distance_meters' => $this->meters($nodes[$pair[0]], $nodes[$pair[1]]), 'lock_count' => 0];
                    WaterwayEdge::query()->firstOrCreate(['from_node_id' => $from->id, 'to_node_id' => $to->id], $attributes);
                    $localEdges++;

                    if (($way['tags']['oneway'] ?? null) !== 'yes') {
                        WaterwayEdge::query()->firstOrCreate(['from_node_id' => $to->id, 'to_node_id' => $from->id], $attributes);
                        $localEdges++;
                    }
                }
            }
            $this->info("Import termine : {$localNodes} noeuds ajoutes, {$localEdges} arcs analyses.");
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
