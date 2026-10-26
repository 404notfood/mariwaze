<?php

namespace Tests\Browser;

use App\Services\Marine\Contracts\WeatherProviderInterface;
use App\Services\Marine\EmodnetBathymetryService;
use App\Services\Navigation\PortGeocoder;
use App\Services\Navigation\SeaRouteService;
use Illuminate\Support\ServiceProvider;

class BrowserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PortGeocoder::class, fn () => new class extends PortGeocoder
        {
            public function search(string $query): ?array
            {
                return $query === 'Pointe-à-Pitre' ? [
                    'name' => 'Pointe-à-Pitre', 'latitude' => 16.2408636, 'longitude' => -61.5334077,
                    'region' => 'Guadeloupe', 'country_code' => 'GP',
                ] : null;
            }
        });
        $this->app->bind(SeaRouteService::class, fn () => new class extends SeaRouteService
        {
            public function route(array $origin, array $destination): array
            {
                return [$origin, $destination];
            }

            public function legs(array $points): array
            {
                $legs = [];
                for ($i = 1; $i < count($points); $i++) {
                    $legs[] = [$points[$i - 1], $points[$i]];
                }

                return $legs;
            }
        });
        $this->app->bind(EmodnetBathymetryService::class, fn () => new class extends EmodnetBathymetryService
        {
            public function minDepthAlongPath(array $geometry): ?float
            {
                return 10;
            }
        });
        $this->app->bind(WeatherProviderInterface::class, fn () => new class implements WeatherProviderInterface
        {
            public function conditions(float $latitude, float $longitude): array
            {
                return [];
            }

            public function forecast(float $latitude, float $longitude, \DateTimeInterface $from, int $hours = 24): array
            {
                return array_map(fn (int $i): array => ['time' => \DateTimeImmutable::createFromInterface($from)->modify('+'.$i.' hours')->format('c'), 'wind_knots' => 8.0, 'gusts_knots' => 12.0, 'wave_height_meters' => 0.5], range(0, max(1, $hours) - 1));
            }
        });
    }
}
