<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarineConditionsApiTest extends TestCase
{
    public function test_it_returns_normalized_marine_conditions(): void
    {
        Cache::flush();

        Http::fake([
            'marine-api.open-meteo.com/*' => Http::response([
                'latitude' => 43.4,
                'longitude' => -1.6,
                'timezone' => 'Europe/Paris',
                'current' => [
                    'time' => '2026-07-27T08:00',
                    'ocean_current_velocity' => 0.8,
                    'ocean_current_direction' => 45,
                    'wave_height' => 0.7,
                    'wave_direction' => 320,
                    'wave_period' => 6,
                    'swell_wave_height' => 0.4,
                ],
            ]),
            'api.open-meteo.com/*' => Http::response([
                'timezone' => 'Europe/Paris',
                'current' => [
                    'wind_speed_10m' => 12,
                    'wind_direction_10m' => 30,
                    'wind_gusts_10m' => 18,
                ],
            ]),
        ]);

        $response = $this->getJson('/api/marine/conditions?latitude=43.4&longitude=-1.6');

        $response
            ->assertOk()
            ->assertJsonPath('source', 'Open-Meteo Marine')
            ->assertJsonPath('current.speed_knots', 0.8)
            ->assertJsonPath('current.direction_degrees', 45)
            ->assertJsonPath('waves.height_meters', 0.7)
            ->assertJsonPath('wind.speed_knots', 12);
    }

    public function test_it_requires_valid_coordinates(): void
    {
        $this->getJson('/api/marine/conditions?latitude=92&longitude=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_it_returns_a_service_unavailable_response_when_the_provider_cannot_be_reached(): void
    {
        Cache::flush();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Network unavailable'));

        $this->getJson('/api/marine/conditions?latitude=43.4&longitude=-1.6')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Les données marines sont temporairement indisponibles. Réessayez dans quelques minutes.');
    }
}
