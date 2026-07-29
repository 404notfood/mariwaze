<?php

namespace App\Services\Marine;

use App\Exceptions\MarineConditionsUnavailable;
use App\Services\Marine\Contracts\WeatherProviderInterface;
use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenMeteoMarineService implements WeatherProviderInterface
{
    /**
     * Retrieve a concise marine snapshot for product displays. This is not navigation-grade data.
     *
     * @return array<string, mixed>
     */
    public function conditions(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('marine:conditions:%0.3f:%0.3f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($latitude, $longitude): array {
            try {
                $marine = Http::acceptJson()
                    ->timeout(config('services.open_meteo.timeout'))
                    ->get(config('services.open_meteo.marine_url'), [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'current' => 'ocean_current_velocity,ocean_current_direction,wave_height,wave_direction,wave_period,swell_wave_height',
                        'timezone' => 'auto',
                        'cell_selection' => 'sea',
                        'velocity_unit' => 'kn',
                    ])
                    ->throw()
                    ->json();

                $weather = Http::acceptJson()
                    ->timeout(config('services.open_meteo.timeout'))
                    ->get(config('services.open_meteo.weather_url'), [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'current' => 'wind_speed_10m,wind_direction_10m,wind_gusts_10m',
                        'timezone' => 'auto',
                        'wind_speed_unit' => 'kn',
                    ])
                    ->throw()
                    ->json();

            } catch (ConnectionException|RequestException $exception) {
                throw new MarineConditionsUnavailable('Les données marines sont temporairement indisponibles.', previous: $exception);
            }

            $current = $marine['current'] ?? [];
            $wind = $weather['current'] ?? [];

            return [
                'source' => 'Open-Meteo Marine',
                'coordinates' => [
                    'latitude' => $marine['latitude'] ?? $latitude,
                    'longitude' => $marine['longitude'] ?? $longitude,
                ],
                'observed_at' => $current['time'] ?? null,
                'timezone' => $marine['timezone'] ?? $weather['timezone'] ?? 'GMT',
                'current' => [
                    'speed_knots' => $current['ocean_current_velocity'] ?? null,
                    'direction_degrees' => $current['ocean_current_direction'] ?? null,
                ],
                'waves' => [
                    'height_meters' => $current['wave_height'] ?? null,
                    'direction_degrees' => $current['wave_direction'] ?? null,
                    'period_seconds' => $current['wave_period'] ?? null,
                    'swell_height_meters' => $current['swell_wave_height'] ?? null,
                ],
                'wind' => [
                    'speed_knots' => $wind['wind_speed_10m'] ?? null,
                    'direction_degrees' => $wind['wind_direction_10m'] ?? null,
                    'gusts_knots' => $wind['wind_gusts_10m'] ?? null,
                ],
                'disclaimer' => 'Données de prévision à consulter avec la documentation nautique officielle. Elles ne remplacent ni les avis aux navigateurs ni les instruments du bord.',
            ];
        });
    }

    /**
     * Prévision horaire (vent, rafales, hauteur de vagues) sur une fenêtre donnée.
     *
     * @return list<array{time: string, wind_knots: float|null, gusts_knots: float|null, wave_height_meters: float|null}>
     */
    public function forecast(float $latitude, float $longitude, DateTimeInterface $from, int $hours = 24): array
    {
        $start = $from->format('Y-m-d');
        $end = (clone \DateTimeImmutable::createFromInterface($from))
            ->modify('+'.max(1, (int) ceil($hours / 24)).' day')
            ->format('Y-m-d');

        $cacheKey = sprintf('marine:forecast:%0.3f:%0.3f:%s:%d', $latitude, $longitude, $start, $hours);

        return Cache::remember($cacheKey, now()->addHours(3), function () use ($latitude, $longitude, $start, $end, $from, $hours): array {
            try {
                $marine = Http::acceptJson()
                    ->timeout(config('services.open_meteo.timeout'))
                    ->get(config('services.open_meteo.marine_url'), [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'hourly' => 'wave_height,swell_wave_height',
                        'start_date' => $start,
                        'end_date' => $end,
                        'timezone' => 'auto',
                        'cell_selection' => 'sea',
                    ])
                    ->throw()
                    ->json();

                $weather = Http::acceptJson()
                    ->timeout(config('services.open_meteo.timeout'))
                    ->get(config('services.open_meteo.weather_url'), [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'hourly' => 'wind_speed_10m,wind_gusts_10m',
                        'start_date' => $start,
                        'end_date' => $end,
                        'timezone' => 'auto',
                        'wind_speed_unit' => 'kn',
                    ])
                    ->throw()
                    ->json();
            } catch (ConnectionException|RequestException $exception) {
                throw new MarineConditionsUnavailable('Les prévisions marines sont temporairement indisponibles.', previous: $exception);
            }

            $times = $weather['hourly']['time'] ?? $marine['hourly']['time'] ?? [];
            $winds = $weather['hourly']['wind_speed_10m'] ?? [];
            $gusts = $weather['hourly']['wind_gusts_10m'] ?? [];
            $waves = $marine['hourly']['wave_height'] ?? [];

            $startsAt = $from->format('Y-m-d\TH');
            $rows = [];

            foreach ($times as $index => $time) {
                if ($time < $startsAt) {
                    continue;
                }

                $rows[] = [
                    'time' => (string) $time,
                    'wind_knots' => isset($winds[$index]) ? (float) $winds[$index] : null,
                    'gusts_knots' => isset($gusts[$index]) ? (float) $gusts[$index] : null,
                    'wave_height_meters' => isset($waves[$index]) ? (float) $waves[$index] : null,
                ];

                if (count($rows) >= $hours) {
                    break;
                }
            }

            return $rows;
        });
    }
}
