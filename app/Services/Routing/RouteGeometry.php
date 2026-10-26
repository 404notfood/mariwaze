<?php

namespace App\Services\Routing;

use App\Support\GeoScope;

class RouteGeometry
{
    private const SNAP_REPLACE_NM = 25.0;

    /**
     * @param  list<list<array{latitude: float, longitude: float}>>  $legs
     * @param  list<array{latitude: float, longitude: float}>  $chain
     * @return list<list<array{latitude: float, longitude: float}>>
     */
    public function stitch(array $legs, array $chain): array
    {
        foreach ($legs as $index => $leg) {
            $from = $chain[$index];
            $to = $chain[$index + 1];

            if ($leg === []) {
                $legs[$index] = [$from, $to];

                continue;
            }

            $leg = $this->trim($leg, $from, $to);
            $firstGap = GeoScope::nauticalMiles($from['latitude'], $from['longitude'], $leg[0]['latitude'], $leg[0]['longitude']);

            if ($firstGap > 0.05) {
                if (count($leg) >= 2 && $firstGap <= self::SNAP_REPLACE_NM) {
                    $leg[0] = $from;
                } else {
                    array_unshift($leg, $from);
                }
            }

            $lastGap = GeoScope::nauticalMiles($to['latitude'], $to['longitude'], $leg[count($leg) - 1]['latitude'], $leg[count($leg) - 1]['longitude']);

            if ($lastGap > 0.05) {
                if (count($leg) >= 2 && $lastGap <= self::SNAP_REPLACE_NM) {
                    $leg[count($leg) - 1] = $to;
                } else {
                    $leg[] = $to;
                }
            }

            $legs[$index] = $leg;
        }

        return $legs;
    }

    /**
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @return list<array{point: array{latitude: float, longitude: float}, index: int}>
     */
    public function split(array $geometry, float $maxDistance): array
    {
        $waypoints = [];
        $accumulated = 0.0;

        for ($i = 1; $i < count($geometry); $i++) {
            $accumulated += GeoScope::nauticalMiles(
                $geometry[$i - 1]['latitude'],
                $geometry[$i - 1]['longitude'],
                $geometry[$i]['latitude'],
                $geometry[$i]['longitude'],
            );

            if ($accumulated >= $maxDistance) {
                $waypoints[] = ['point' => $geometry[$i], 'index' => $i];
                $accumulated = 0.0;
            }
        }

        $lastIndex = count($geometry) - 1;

        if ($waypoints === [] || $waypoints[count($waypoints) - 1]['index'] !== $lastIndex) {
            $waypoints[] = ['point' => $geometry[$lastIndex], 'index' => $lastIndex];
        }

        return $waypoints;
    }

    /** @param list<array{latitude: float, longitude: float}> $geometry */
    public function distance(array $geometry): float
    {
        $total = 0.0;

        for ($i = 1; $i < count($geometry); $i++) {
            $total += GeoScope::nauticalMiles(
                $geometry[$i - 1]['latitude'],
                $geometry[$i - 1]['longitude'],
                $geometry[$i]['latitude'],
                $geometry[$i]['longitude'],
            );
        }

        return $total;
    }

    /**
     * @param  non-empty-list<array{latitude: float, longitude: float}>  $leg
     * @param  array{latitude: float, longitude: float}  $from
     * @param  array{latitude: float, longitude: float}  $to
     * @return non-empty-list<array{latitude: float, longitude: float}>
     */
    private function trim(array $leg, array $from, array $to): array
    {
        $end = count($leg) - 1;

        while ($end >= 1
            && GeoScope::nauticalMiles($leg[$end - 1]['latitude'], $leg[$end - 1]['longitude'], $to['latitude'], $to['longitude'])
                <= GeoScope::nauticalMiles($leg[$end]['latitude'], $leg[$end]['longitude'], $to['latitude'], $to['longitude'])) {
            $end--;
        }

        $start = 0;

        while ($start < $end
            && GeoScope::nauticalMiles($leg[$start + 1]['latitude'], $leg[$start + 1]['longitude'], $from['latitude'], $from['longitude'])
                <= GeoScope::nauticalMiles($leg[$start]['latitude'], $leg[$start]['longitude'], $from['latitude'], $from['longitude'])) {
            $start++;
        }

        $trimmed = array_slice($leg, $start, $end - $start + 1);

        return $trimmed === [] ? [$leg[0]] : $trimmed;
    }
}
