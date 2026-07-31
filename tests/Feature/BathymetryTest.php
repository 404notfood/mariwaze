<?php

namespace Tests\Feature;

use App\Services\Marine\EmodnetBathymetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BathymetryTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{latitude: float, longitude: float}> */
    private array $path = [
        ['latitude' => 48.3, 'longitude' => -4.9],
        ['latitude' => 48.4, 'longitude' => -5.1],
        ['latitude' => 48.5, 'longitude' => -5.2],
    ];

    public function test_the_minimum_depth_is_the_shallowest_point_of_the_profile(): void
    {
        // Profil EMODnet : altitudes négatives sous le zéro hydrographique.
        // Le haut-fond (-13.1/-12.4) couvre deux échantillons : il compte.
        Http::fake(['rest.emodnet-bathymetry.eu/*' => Http::response([
            -0.2, -48.9, -13.1, -12.4, -60.1, -55.0, -0.1,
        ])]);

        $depth = app(EmodnetBathymetryService::class)->minDepthAlongPath($this->path);

        // Extrémités exclues (approches ~0 m), paire la moins profonde retenue.
        $this->assertSame(13.1, $depth);
    }

    public function test_an_unavailable_service_returns_null(): void
    {
        Http::fake(['rest.emodnet-bathymetry.eu/*' => Http::response('erreur', 503)]);

        $this->assertNull(app(EmodnetBathymetryService::class)->minDepthAlongPath($this->path));
    }

    public function test_a_short_geometry_returns_null_without_calling_the_api(): void
    {
        Http::fake();

        $this->assertNull(app(EmodnetBathymetryService::class)->minDepthAlongPath([
            ['latitude' => 48.3, 'longitude' => -4.9],
        ]));

        Http::assertNothingSent();
    }
}
