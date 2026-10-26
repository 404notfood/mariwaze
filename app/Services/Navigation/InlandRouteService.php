<?php

namespace App\Services\Navigation;

use App\Models\WaterwayEdge;
use App\Models\WaterwayNode;
use SplPriorityQueue;

class InlandRouteService
{
    /**
     * @param  array{latitude: float, longitude: float}  $origin
     * @param  array{latitude: float, longitude: float}  $destination
     * @return array{geometry: array<int, array{latitude: float, longitude: float}>, miles: int, locks: int}|null
     */
    public function route(array $origin, array $destination): ?array
    {
        $start = $this->nearestNode($origin);
        $end = $this->nearestNode($destination);

        if ($start === null || $end === null || $start['distance'] > 5000 || $end['distance'] > 5000) {
            return null;
        }

        $directDistance = $this->meters($origin['latitude'], $origin['longitude'], $destination['latitude'], $destination['longitude']);
        $padding = max(0.5, min(5.0, ($directDistance / 111000) * 0.25));
        $latitudeBounds = [min($origin['latitude'], $destination['latitude']) - $padding, max($origin['latitude'], $destination['latitude']) + $padding];
        $longitudeBounds = [min($origin['longitude'], $destination['longitude']) - $padding, max($origin['longitude'], $destination['longitude']) + $padding];

        $nodeQuery = WaterwayNode::query()
            ->whereBetween('latitude', $latitudeBounds)
            ->whereBetween('longitude', $longitudeBounds);
        $nodes = (clone $nodeQuery)->get(['id', 'latitude', 'longitude'])->keyBy('id');

        if (! $nodes->has($start['id']) || ! $nodes->has($end['id'])) {
            return null;
        }

        $graph = [];
        $nodeIds = (clone $nodeQuery)->select('id');
        foreach (WaterwayEdge::query()
            ->whereIn('from_node_id', clone $nodeIds)
            ->whereIn('to_node_id', clone $nodeIds)
            ->get() as $edge) {
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
        $routeNodeIds = [$start['id'], ...array_map(fn (WaterwayEdge $edge): int => $edge->to_node_id, $edges)];

        return [
            'geometry' => [
                ['latitude' => $origin['latitude'], 'longitude' => $origin['longitude']],
                ...collect($routeNodeIds)->map(fn (int $id): array => ['latitude' => $nodes[$id]->latitude, 'longitude' => $nodes[$id]->longitude])->all(),
                ['latitude' => $destination['latitude'], 'longitude' => $destination['longitude']],
            ],
            'miles' => (int) round($distances[$end['id']] / 1852),
            'locks' => array_sum(array_map(fn (WaterwayEdge $edge): int => $edge->lock_count, $edges)),
        ];
    }

    /**
     * @param  array{latitude: float, longitude: float}  $point
     * @return array{id: int, distance: float}|null
     */
    private function nearestNode(array $point): ?array
    {
        $longitudeFactor = max(0.01, cos(deg2rad($point['latitude'])));
        $node = WaterwayNode::query()
            ->orderByRaw(
                '((latitude - ?) * (latitude - ?)) + (((longitude - ?) * ?) * ((longitude - ?) * ?))',
                [$point['latitude'], $point['latitude'], $point['longitude'], $longitudeFactor, $point['longitude'], $longitudeFactor],
            )
            ->first(['id', 'latitude', 'longitude']);

        return $node === null ? null : [
            'id' => $node->id,
            'distance' => $this->meters($point['latitude'], $point['longitude'], $node->latitude, $node->longitude),
        ];
    }

    private function meters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitude = deg2rad($latitudeB - $latitudeA);
        $longitude = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitude / 2) ** 2 + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitude / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
