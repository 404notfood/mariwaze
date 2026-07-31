<?php

namespace Tests\Feature;

use App\Models\HazardZone;
use App\Services\Safety\CoastalCountryResolver;
use App\Services\Safety\RouteHazardService;
use Database\Seeders\HazardZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RouteHazardTest extends TestCase
{
    use RefreshDatabase;

    /** Tracé simplifié Dinard → Marseille par le cap Finisterre et Gibraltar. */
    private const DINARD_MARSEILLE = [
        ['latitude' => 48.63, 'longitude' => -2.05],
        ['latitude' => 48.70, 'longitude' => -5.40],
        ['latitude' => 43.10, 'longitude' => -9.80],
        ['latitude' => 38.40, 'longitude' => -9.70],
        ['latitude' => 36.90, 'longitude' => -9.20],
        ['latitude' => 35.95, 'longitude' => -5.60],
        ['latitude' => 37.50, 'longitude' => -0.60],
        ['latitude' => 43.29, 'longitude' => 5.37],
    ];

    public function test_hazard_zones_are_reported_along_the_route(): void
    {
        $this->seed(HazardZoneSeeder::class);

        $alerts = app(RouteHazardService::class)->alertsForGeometry(
            self::DINARD_MARSEILLE,
            Carbon::parse('2026-08-10'),
            21,
        );

        $slugs = array_column($alerts, 'slug');

        $this->assertContains('dst-ouessant', $slugs);
        $this->assertContains('orques-gibraltar', $slugs);
        $this->assertContains('dst-finisterre', $slugs);

        // Les zones lointaines (piraterie golfe de Guinée…) ne remontent pas.
        $this->assertNotContains('piraterie-golfe-guinee', $slugs);
        $this->assertNotContains('piraterie-malacca', $slugs);

        // Les dangers sont classés avant les simples vigilances.
        $this->assertSame('danger', $alerts[0]['severity']);
    }

    public function test_seasonality_is_flagged_from_cruise_dates(): void
    {
        HazardZone::create([
            'slug' => 'test-orques',
            'name' => 'Zone orques test',
            'category' => 'orca',
            'severity' => 'danger',
            'latitude' => 36.0,
            'longitude' => -5.8,
            'radius_nm' => 40,
            'description' => 'Zone de test.',
            'months' => [6, 7, 8],
        ]);

        $service = app(RouteHazardService::class);
        $geometry = [
            ['latitude' => 35.95, 'longitude' => -5.60],
            ['latitude' => 36.20, 'longitude' => -5.90],
        ];

        $summer = $service->alertsForGeometry($geometry, Carbon::parse('2026-07-05'), 7);
        $winter = $service->alertsForGeometry($geometry, Carbon::parse('2026-01-05'), 7);

        $this->assertTrue($summer[0]['in_season']);
        $this->assertFalse($winter[0]['in_season']);
    }

    public function test_an_empty_geometry_returns_no_alert(): void
    {
        $this->seed(HazardZoneSeeder::class);

        $this->assertSame([], app(RouteHazardService::class)->alertsForGeometry([]));
    }

    public function test_a_polygon_zone_is_detected_when_the_route_crosses_it(): void
    {
        HazardZone::create([
            'slug' => 'test-dst-polygone',
            'name' => 'DST test',
            'category' => 'traffic',
            'severity' => 'danger',
            'latitude' => 48.7,
            'longitude' => -5.5,
            'radius_nm' => 30,
            'description' => 'Zone de test polygonale.',
            'geometry' => [
                ['latitude' => 48.5, 'longitude' => -5.8],
                ['latitude' => 48.9, 'longitude' => -5.8],
                ['latitude' => 48.9, 'longitude' => -5.2],
                ['latitude' => 48.5, 'longitude' => -5.2],
            ],
        ]);

        $service = app(RouteHazardService::class);

        // Traverse le carré : alerte « sur le trajet » avec le contour.
        $crossing = $service->alertsForGeometry([
            ['latitude' => 48.7, 'longitude' => -6.5],
            ['latitude' => 48.7, 'longitude' => -4.5],
        ]);

        $this->assertTrue($crossing[0]['on_route']);
        $this->assertCount(4, $crossing[0]['geometry']);

        // Passe au sud du carré sans le toucher : « à proximité » seulement.
        $nearby = $service->alertsForGeometry([
            ['latitude' => 48.3, 'longitude' => -6.5],
            ['latitude' => 48.3, 'longitude' => -4.5],
        ]);

        $this->assertFalse($nearby[0]['on_route']);
        $this->assertGreaterThan(0, $nearby[0]['distance_nm']);
    }

    public function test_coastal_countries_are_resolved_along_the_route(): void
    {
        $resolver = app(CoastalCountryResolver::class);

        $this->assertSame('FR', $resolver->resolve(48.9, -2.2));
        $this->assertSame('PT', $resolver->resolve(38.5, -9.6));
        $this->assertSame('ES', $resolver->resolve(43.0, -9.5));
        // Plein océan : aucun pays.
        $this->assertNull($resolver->resolve(45.8, -6.5));
    }
}
