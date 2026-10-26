<?php

namespace Tests\Feature;

use App\Jobs\GenerateCruiseRoutes;
use App\Models\Cruise;
use App\Models\PlaceCoordinate;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Marine\Contracts\WeatherProviderInterface;
use App\Services\Marine\EmodnetBathymetryService;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\PortGeocoder;
use App\Services\Navigation\SeaRouteService;
use App\Services\Routing\RouteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class WorldwidePlacesTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_a_worldwide_match_takes_precedence_over_a_mainland_homonym(): void
    {
        $local = $this->mainlandHomonym();
        config()->set('services.geocoding.mapbox_token', 'test-token');
        Http::fake(['api.mapbox.com/*' => Http::response(['features' => [[
            'name' => 'Pointe-à-Pitre', 'geometry' => ['coordinates' => [-61.5334077, 16.2408636]],
        ]]])]);

        $resolver = app(PortCoordinates::class);
        $this->assertSame(-61.5334077, $resolver->find('Pointe-à-Pitre')['longitude']);
        $this->assertSame(1.98937, $resolver->find('Pointe à Pitre', 'locality:'.$local->id)['longitude']);
        Http::assertSentCount(1);
    }

    public function test_gps_points_can_be_saved_and_resolved_without_any_geocoder(): void
    {
        $this->mock(PortGeocoder::class)->shouldNotReceive('locate');
        $this->post('/croisieres', array_replace($this->payload(), [
            'departure_port' => 'GPS 48.64900, -2.02500', 'departure_place' => 'gps:48.64900,-2.02500',
            'destination_port' => 'GPS 16.24086, -61.53341', 'destination_place' => 'gps:16.24086,-61.53341',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $cruise = Cruise::firstOrFail();
        $location = app(PortCoordinates::class)->find($cruise->destination_port, $cruise->destination_place);
        $this->assertSame(16.24086, $location['latitude']);
        $this->assertSame(-61.53341, $location['longitude']);
        $this->assertDatabaseCount('place_coordinates', 0);
    }

    public function test_invalid_or_out_of_range_gps_coordinates_are_rejected(): void
    {
        foreach (['gps:91,0', 'gps:0,181', 'gps:NaN,0', 'gps:0,-181', 'gps:48,2,3'] as $reference) {
            $this->assertNull(app(PortCoordinates::class)->referenced($reference));
            $this->post('/croisieres', array_replace($this->payload(), ['destination_place' => $reference]))
                ->assertSessionHasErrors('destination_port');
        }
    }

    public function test_local_autocomplete_includes_geodata_and_never_calls_a_remote_provider(): void
    {
        $local = $this->mainlandHomonym();
        Http::preventStrayRequests();
        $this->getJson('/api/places?q=Pointe-à-Pitre')
            ->assertOk()->assertJsonCount(1, 'places')
            ->assertJsonPath('places.0.reference', 'locality:'.$local->id)
            ->assertJsonPath('places.0.country_code', 'FR');
        Http::assertNothingSent();
    }

    public function test_worldwide_selection_is_preserved_through_creation_and_route_generation(): void
    {
        $this->mainlandHomonym();
        $this->mock(PortGeocoder::class, function ($mock): void {
            $mock->shouldReceive('search')->once()->with('Pointe-à-Pitre')->andReturn([
                'name' => 'Pointe-à-Pitre', 'latitude' => 16.2408636, 'longitude' => -61.5334077,
                'region' => 'Guadeloupe', 'country_code' => 'GP',
            ]);
            $mock->shouldReceive('canPersist')->once()->andReturn(true);
            $mock->shouldNotReceive('locate');
        });
        $place = $this->getJson('/api/places?q=Pointe-à-Pitre&worldwide=1')->assertOk()
            ->assertJsonCount(1, 'places')->assertJsonPath('places.0.country_code', 'GP')->json('places.0');
        $this->assertStringStartsWith('locality:', $place['reference']);
        $this->getJson('/api/places?q=Pointe-à-Pitre')->assertOk()
            ->assertJsonPath('places.1.region', 'Guadeloupe');

        $this->post('/croisieres', $this->payload() + [
            'destination_place' => $place['reference'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $cruise = Cruise::firstOrFail();
        $cruise->update(['route_generation_token' => 'worldwide-test', 'route_generation_status' => 'pending']);
        $this->mock(SeaRouteService::class, function ($mock): void {
            $mock->shouldReceive('route')->once()->withArgs(fn ($from, $to) => $from['longitude'] === -2.025 && $to['longitude'] === -61.5334077)
                ->andReturn([
                    ['latitude' => 48.649, 'longitude' => -2.025],
                    ['latitude' => 35.0, 'longitude' => -35.0],
                    ['latitude' => 16.2408636, 'longitude' => -61.5334077],
                ]);
            $mock->shouldReceive('legs')->andReturnUsing(function (array $points): array {
                $legs = [];
                foreach (range(1, count($points) - 1) as $index) {
                    $legs[] = [$points[$index - 1], $points[$index]];
                }

                return $legs;
            });
        });
        $this->mock(EmodnetBathymetryService::class)->shouldReceive('minDepthAlongPath')->andReturn(null);
        $this->mock(WeatherProviderInterface::class)->shouldReceive('forecast')->andReturn([]);

        (new GenerateCruiseRoutes($cruise->id, 'worldwide-test'))->handle(app(RouteGenerator::class));
        $this->assertSame('completed', $cruise->fresh()->route_generation_status);
        $this->assertCount(3, $cruise->routes);
        foreach ($cruise->routes as $route) {
            $this->assertGreaterThan(3000, $route->distance_nm);
            $this->assertSame(-61.5334077, $route->geometry[array_key_last($route->geometry)]['longitude']);
            $this->assertFalse($route->segments->last()->weather_snapshot['available']);
        }
    }

    public function test_temporary_mapbox_results_are_never_persisted(): void
    {
        config()->set('services.geocoding.mapbox_token', 'test-token');
        config()->set('services.geocoding.mapbox_permanent', false);
        Http::fake(['api.mapbox.com/*' => Http::response(['features' => [[
            'name' => 'Lisbonne', 'geometry' => ['coordinates' => [-9.1366, 38.7078]],
        ]]])]);
        $this->getJson('/api/places?q=Lisbonne&worldwide=1')->assertOk()
            ->assertJsonPath('places.0.name', 'Lisbonne')
            ->assertJsonPath('places.0.reference', null);
        $this->assertDatabaseCount('place_coordinates', 0);
    }

    public function test_automatic_duration_requests_do_not_trigger_remote_autocomplete(): void
    {
        $this->mock(PortGeocoder::class)->shouldNotReceive('locate');
        $this->getJson('/api/cruises/duration?departure_port=Saint-Malo&destination_port=Lisbonne&navigation_style=Confort')
            ->assertOk()->assertJsonPath('days', 7);
    }

    public function test_automatic_crossings_can_exceed_the_manual_duration_limit(): void
    {
        $this->mock(CruiseDurationEstimator::class)->shouldReceive('estimate')->once()->andReturn(200);
        $this->post('/croisieres', array_replace($this->payload(), ['auto_duration' => true, 'duration_days' => 200]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(200, Cruise::firstOrFail()->duration_days);
    }

    public function test_nominatim_returns_a_short_name_and_country_for_explicit_search(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config()->set('services.geocoding.mapbox_token', null);
        config()->set('services.geocoding.nominatim_url', 'https://geocoder.example.test/search');
        Http::fake(['geocoder.example.test/*' => Http::response([[
            'name' => 'Pointe-à-Pitre', 'display_name' => 'Pointe-à-Pitre, Guadeloupe, France',
            'lat' => '16.2408636', 'lon' => '-61.5334077',
            'address' => ['state' => 'Guadeloupe', 'country_code' => 'gp'],
        ]])]);
        $this->getJson('/api/places?q=Pointe-à-Pitre&worldwide=1')->assertOk()
            ->assertJsonPath('places.0.name', 'Pointe-à-Pitre')
            ->assertJsonPath('places.0.region', 'Guadeloupe')
            ->assertJsonPath('places.0.country_code', 'GP');
        Http::assertSentCount(1);
    }

    private function mainlandHomonym(): PlaceCoordinate
    {
        return PlaceCoordinate::create([
            'name' => 'Pointe à Pitre', 'normalized_name' => 'pointe-a-pitre',
            'latitude' => 44.07984, 'longitude' => 1.98937,
            'feature_class' => 'P', 'feature_code' => 'PPL', 'country_code' => 'FR',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['title' => 'Saint-Malo Guadeloupe', 'departure_port' => 'Saint-Malo', 'destination_port' => 'Pointe-à-Pitre',
            'departure_date' => now()->addWeek()->toDateString(), 'duration_days' => 100, 'auto_duration' => false,
            'crew_count' => 2, 'navigation_style' => 'Confort'];
    }
}
