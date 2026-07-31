<?php

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Process\Process;

class SeaRouteService
{
    /**
     * @param  array{latitude: float, longitude: float}  $origin
     * @param  array{latitude: float, longitude: float}  $destination
     * @return list<array{latitude: float, longitude: float}>
     */
    public function route(array $origin, array $destination): array
    {
        // v3 : routeur côtier fin, simplification qui ne coupe jamais la terre.
        $key = sprintf('searoute:v3:%0.3f:%0.3f:%0.3f:%0.3f', $origin['latitude'], $origin['longitude'], $destination['latitude'], $destination['longitude']);

        return Cache::remember($key, now()->addDays(30), function () use ($origin, $destination): array {
            return $this->toPoints($this->run(['origin' => $origin, 'destination' => $destination], 30));
        });
    }

    /**
     * Trace la route maritime passant par chaque point de passage, en une seule
     * invocation du moteur. Retourne un tracé par étape.
     *
     * @param  list<array{latitude: float, longitude: float}>  $points
     * @return list<list<array{latitude: float, longitude: float}>>
     */
    public function legs(array $points): array
    {
        if (count($points) < 2) {
            return [];
        }

        $rounded = array_map(fn (array $point): array => [
            round($point['latitude'], 3),
            round($point['longitude'], 3),
        ], $points);
        $key = 'searoute:legs:v3:'.md5(json_encode($rounded, JSON_THROW_ON_ERROR));

        return Cache::remember($key, now()->addDays(30), function () use ($points): array {
            $payload = $this->run(['waypoints' => $points], 60);
            $rawLegs = is_array($payload['legs'] ?? null) ? $payload['legs'] : [];

            $legs = [];

            foreach ($rawLegs as $rawLeg) {
                $legs[] = $this->toPoints(is_array($rawLeg) ? $rawLeg : []);
            }

            return $legs;
        });
    }

    /**
     * Convertit une liste de coordonnées [lon, lat] du moteur en points nommés.
     *
     * @param  array<mixed>  $coordinates
     * @return list<array{latitude: float, longitude: float}>
     */
    private function toPoints(array $coordinates): array
    {
        $points = [];

        foreach ($coordinates as $coordinate) {
            if (is_array($coordinate) && is_numeric($coordinate[0] ?? null) && is_numeric($coordinate[1] ?? null)) {
                $points[] = [
                    'longitude' => (float) $coordinate[0],
                    'latitude' => (float) $coordinate[1],
                ];
            }
        }

        return $points;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<mixed>
     */
    private function run(array $input, int $timeout): array
    {
        $process = new Process([
            config('services.searoute.python'),
            base_path('scripts/maritime_route.py'),
        ], env: ['PYTHONPATH' => base_path('.python-packages')]);
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Le moteur de route maritime est indisponible: '.$process->getErrorOutput());
        }

        return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }
}
