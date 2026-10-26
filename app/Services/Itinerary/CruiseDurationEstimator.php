<?php

namespace App\Services\Itinerary;

use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\SeaRouteService;

class CruiseDurationEstimator
{
    public function __construct(
        private readonly PortCoordinates $ports,
        private readonly SeaRouteService $router,
    ) {}

    /**
     * Durée conseillée, calculée sur la distance maritime réelle et le rythme
     * quotidien associé au style de navigation.
     */
    public function estimate(string $departure, string $destination, string $style, ?string $departurePlace = null, ?string $destinationPlace = null): int
    {
        $start = $this->ports->find($departure, $departurePlace);
        $end = $this->ports->find($destination, $destinationPlace);

        if ($start === null || $end === null) {
            return 7;
        }

        $dailyMiles = match ($style) {
            'Rapide' => 55,
            'Découverte' => 25,
            'Famille' => 22,
            default => 35,
        };

        return max(2, (int) ceil($this->maritimeMiles($start, $end) / $dailyMiles) + 1);
    }

    /**
     * @param  array{latitude: float, longitude: float}  $start
     * @param  array{latitude: float, longitude: float}  $end
     */
    private function maritimeMiles(array $start, array $end): float
    {
        try {
            $route = $this->router->route($start, $end);
            $miles = 0.0;

            for ($index = 1; $index < count($route); $index++) {
                $miles += $this->nauticalMiles(
                    $route[$index - 1]['latitude'],
                    $route[$index - 1]['longitude'],
                    $route[$index]['latitude'],
                    $route[$index]['longitude'],
                );
            }

            return $miles;
        } catch (\RuntimeException) {
            return $this->nauticalMiles($start['latitude'], $start['longitude'], $end['latitude'], $end['longitude']);
        }
    }

    private function nauticalMiles(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitude = deg2rad($latitudeB - $latitudeA);
        $longitude = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitude / 2) ** 2 + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitude / 2) ** 2;

        return 3440.065 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
