<?php

namespace Tests\Feature;

use App\Services\Navigation\SeaRouteService;
use Database\Seeders\HazardZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class RoutePreviewApiTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    /** @param array<int, array{latitude: float, longitude: float}> $geometry */
    private function fakeSeaRoute(array $geometry): void
    {
        $this->app->instance(SeaRouteService::class, new class($geometry) extends SeaRouteService
        {
            /** @param array<int, array{latitude: float, longitude: float}> $geometry */
            public function __construct(private readonly array $geometry) {}

            public function route(array $origin, array $destination): array
            {
                if ($this->geometry === []) {
                    throw new RuntimeException('Moteur indisponible');
                }

                return $this->geometry;
            }
        });
    }

    public function test_a_route_preview_is_computed_between_two_points(): void
    {
        $this->seed(HazardZoneSeeder::class);
        $this->fakeSeaRoute([
            ['latitude' => 48.63, 'longitude' => -2.05],
            ['latitude' => 48.70, 'longitude' => -5.40],
            ['latitude' => 47.00, 'longitude' => -5.00],
        ]);

        $response = $this->getJson('/api/routes/preview?from_latitude=48.63&from_longitude=-2.05&to_latitude=47.0&to_longitude=-5.0');

        $response->assertOk()
            ->assertJsonStructure(['geometry', 'distance_nm', 'hazards', 'departure_port', 'arrival_port']);

        $this->assertGreaterThan(0, $response->json('distance_nm'));
        // Le tracé passe dans le rail d'Ouessant : l'alerte doit remonter.
        $this->assertContains('dst-ouessant', array_column($response->json('hazards'), 'slug'));
    }

    public function test_an_unroutable_pair_of_points_returns_an_explicit_error(): void
    {
        $this->fakeSeaRoute([]);

        $this->getJson('/api/routes/preview?from_latitude=48.63&from_longitude=-2.05&to_latitude=47.0&to_longitude=-5.0')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_coordinates_are_validated(): void
    {
        $this->getJson('/api/routes/preview?from_latitude=999&from_longitude=-2.05&to_latitude=47.0&to_longitude=-5.0')
            ->assertStatus(422);
    }
}
