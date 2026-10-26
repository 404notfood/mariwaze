<?php

namespace Tests\Feature;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\Port;
use Database\Seeders\MaritimeSafetySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

/**
 * Vérifie que chaque page maritime s'affiche à partir de la base, sans
 * fournisseur de données de démonstration.
 */
class MaritimePagesTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_the_dashboard_opens_without_any_data(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('dashboardData.boat', null)
                ->where('dashboardData.cruise', null)
                ->where('dashboardData.readiness', 0)
                ->has('dashboardData.alerts', 0)
            );
    }

    public function test_the_dashboard_shows_the_real_boat_and_cruise(): void
    {
        $boat = Boat::create([
            'user_id' => $this->accountUser->id,
            'organization_id' => $this->accountOrganization->id,
            'name' => 'Éole', 'boat_type' => 'voilier', 'length' => 12.4, 'width' => 3.8,
            'draft' => 2.1, 'avg_speed' => 6.5, 'mast_height' => 18.5,
            'equipment_json' => ['vhf' => true, 'lifejackets' => true],
        ]);

        Cruise::create([
            'user_id' => $this->accountUser->id,
            'organization_id' => $this->accountOrganization->id,
            'slug' => 'corse-2027', 'title' => 'Tour de Corse',
            'departure_port' => 'Marseille', 'destination_port' => 'Bonifacio',
            'departure_date' => now()->addMonth(), 'duration_days' => 7,
            'crew_count' => 2, 'navigation_style' => 'Confort', 'boat_id' => $boat->id,
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboardData.boat.name', 'Éole')
                ->where('dashboardData.cruise.title', 'Tour de Corse')
                // Aucun itinéraire n'a été calculé : rien n'est inventé.
                ->where('dashboardData.cruise.route', null)
                ->has('dashboardData.cruise.next_legs', 0)
            );
    }

    public function test_the_map_page_opens_without_a_route(): void
    {
        $this->get('/carte')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('map')
                ->where('route', null)
                ->has('nearby.ports', 0)
                ->has('nearby.anchorages', 0)
            );
    }

    public function test_the_explore_page_opens_with_an_empty_database(): void
    {
        $this->get('/explorer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('explore')
                ->where('counts.ports', 0)
                ->where('counts.anchorages', 0)
                ->has('spots', 0)
            );
    }

    public function test_the_explore_page_filters_by_type(): void
    {
        Port::create([
            'slug' => 'p1', 'name' => 'Port Alpha', 'latitude' => 43.0, 'longitude' => 5.0,
            'source' => 'test', 'source_ref' => 'p1',
        ]);
        Anchorage::create([
            'slug' => 'a1', 'name' => 'Anse Beta', 'latitude' => 43.1, 'longitude' => 5.1,
            'source' => 'test', 'source_ref' => 'a1',
        ]);

        $this->get('/explorer?type=port')
            ->assertInertia(fn (Assert $page) => $page
                ->has('spots', 1)
                ->where('spots.0.name', 'Port Alpha')
            );

        $this->get('/explorer?type=anchorage')
            ->assertInertia(fn (Assert $page) => $page
                ->has('spots', 1)
                ->where('spots.0.name', 'Anse Beta')
            );
    }

    public function test_the_explore_page_searches_by_name(): void
    {
        Port::create([
            'slug' => 'p1', 'name' => 'Port de Cargèse', 'latitude' => 42.1, 'longitude' => 8.6,
            'source' => 'test', 'source_ref' => 'p1',
        ]);
        Port::create([
            'slug' => 'p2', 'name' => 'Port de Bonifacio', 'latitude' => 41.4, 'longitude' => 9.2,
            'source' => 'test', 'source_ref' => 'p2',
        ]);

        $this->get('/explorer?q=bonifacio')
            ->assertInertia(fn (Assert $page) => $page
                ->has('spots', 1)
                ->where('spots.0.name', 'Port de Bonifacio')
            );
    }

    public function test_a_port_page_shows_its_real_services(): void
    {
        Port::create([
            'slug' => 'port-vauban', 'name' => 'Port Vauban', 'region' => 'Antibes',
            'latitude' => 43.58, 'longitude' => 7.13, 'has_fuel' => true, 'has_water' => true,
            'vhf_channel' => '9', 'source' => 'osm', 'source_ref' => 'node/1',
        ]);

        $this->get('/ports/port-vauban')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('port-show')
                ->where('port.name', 'Port Vauban')
                ->where('port.vhf_channel', '9')
                ->where('port.services', ['Carburant', 'Eau'])
                ->where('port.rating', null)
                ->has('port.reviews', 0)
            );
    }

    public function test_an_unknown_place_returns_a_not_found(): void
    {
        $this->get('/ports/inexistant')->assertNotFound();
        $this->get('/mouillages/inexistant')->assertNotFound();
    }

    public function test_the_security_page_reports_missing_equipment(): void
    {
        $this->seed(MaritimeSafetySeeder::class);

        Boat::create([
            'user_id' => $this->accountUser->id,
            'organization_id' => $this->accountOrganization->id,
            'name' => 'Éole', 'boat_type' => 'voilier', 'length' => 12.4, 'width' => 3.8,
            'draft' => 2.1, 'avg_speed' => 6.5, 'equipment_json' => ['vhf' => true],
        ]);

        $this->get('/securite')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('security')
                ->where('securityData.boat.name', 'Éole')
                ->where('securityData.readiness', 17)
                ->has('securityData.missing', 5)
            );
    }
}
