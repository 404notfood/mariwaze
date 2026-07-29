<?php

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Process\Process;

class SeaRouteService
{
    /** @param array{latitude: float, longitude: float} $origin @param array{latitude: float, longitude: float} $destination
     * @return array<int, array{latitude: float, longitude: float}>
     */
    public function route(array $origin, array $destination): array
    {
        $key = sprintf('searoute:%0.3f:%0.3f:%0.3f:%0.3f', $origin['latitude'], $origin['longitude'], $destination['latitude'], $destination['longitude']);

        return Cache::remember($key, now()->addDays(30), function () use ($origin, $destination): array {
            $process = new Process([
                config('services.searoute.python'),
                base_path('scripts/maritime_route.py'),
            ], env: ['PYTHONPATH' => base_path('.python-packages')]);
            $process->setInput(json_encode(['origin' => $origin, 'destination' => $destination], JSON_THROW_ON_ERROR));
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Le moteur de route maritime est indisponible: '.$process->getErrorOutput());
            }

            $coordinates = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

            return array_map(fn (array $coordinate): array => [
                'longitude' => (float) $coordinate[0],
                'latitude' => (float) $coordinate[1],
            ], $coordinates);
        });
    }
}
