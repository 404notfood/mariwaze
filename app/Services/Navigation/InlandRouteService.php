<?php

namespace App\Services\Navigation;

use App\Models\WaterwayEdge;
use App\Models\WaterwayNode;
use SplPriorityQueue;

class InlandRouteService
{
    /** @param array{latitude: float, longitude: float} $origin @param array{latitude: float, longitude: float} $destination
     * @return array{geometry: array<int, array{latitude: float, longitude: float}>, miles: int, locks: int}|null
     */
    public function route(array $origin, array $destination): ?array
    {
        $nodes = WaterwayNode::query()->get(['id', 'latitude', 'longitude'])->keyBy('id');
        if ($nodes->isEmpty()) {
            return null;
        }

        $start = $this->nearestNode($nodes->all(), $origin);
        $end = $this->nearestNode($nodes->all(), $destination);
        if ($start === null || $end === null || $start['distance'] > 5000 || $end['distance'] > 5000) {
            return null;
        }

        $graph = [];
        foreach (WaterwayEdge::query()->get() as $edge) {
            $graph[$edge->from_node_id][] = $edge;
        }

        $queue = new SplPriorityQueue;
        $queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $queue->insert($start['id'], 0);
        $distances = [$start['id'] => 0.0];
        $previous = [];

        while (! $queue->isEmpty()) {
            $current = $queue->extract();
            $nodeId = $current['data'];
            if (-$current['priority'] > $distances[$nodeId]) {
                continue;
            }
            if ($nodeId === $end['id']) {
                break;
            }

            foreach ($graph[$nodeId] ?? [] as $edge) {
                $candidate = $distances[$nodeId] + $edge->distance_meters;
                if ($candidate >= ($distances[$edge->to_node_id] ?? INF)) {
                    continue;
                }

                $distances[$edge->to_node_id] = $candidate;
                $previous[$edge->to_node_id] = $edge;
                $queue->insert($edge->to_node_id, -$candidate);
            }
        }

        if (! isset($distances[$end['id']])) {
            return null;
        }

        $edges = [];
        for ($nodeId = $end['id']; $nodeId !== $start['id']; $nodeId = $previous[$nodeId]->from_node_id) {
            $edges[] = $previous[$nodeId];
        }
        $edges = array_reverse($edges);
        $nodeIds = [$start['id'], ...array_map(fn (WaterwayEdge $edge): int => $edge->to_node_id, $edges)];

        return [
            'geometry' => [
                ['latitude' => $origin['latitude'], 'longitude' => $origin['longitude']],
                ...collect($nodeIds)->map(fn (int $id): array => ['latitude' => $nodes[$id]->latitude, 'longitude' => $nodes[$id]->longitude])->all(),
                ['latitude' => $destination['latitude'], 'longitude' => $destination['longitude']],
            ],
            'miles' => (int) round($distances[$end['id']] / 1852),
            'locks' => array_sum(array_map(fn (WaterwayEdge $edge): int => $edge->lock_count, $edges)),
        ];
    }

    /** @param array<int, WaterwayNode> $nodes @param array{latitude: float, longitude: float} $point
     * @return array{id: int, distance: float}|null
     */
    private function nearestNode(array $nodes, array $point): ?array
    {
        $nearest = null;
        foreach ($nodes as $node) {
            $distance = $this->meters($point['latitude'], $point['longitude'], $node->latitude, $node->longitude);
            if ($nearest === null || $distance < $nearest['distance']) {
                $nearest = ['id' => $node->id, 'distance' => $distance];
            }
        }

        return $nearest;
    }

    private function meters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitude = deg2rad($latitudeB - $latitudeA);
        $longitude = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitude / 2) ** 2 + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitude / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
