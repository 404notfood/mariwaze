<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CruiseCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cruise_can_be_created_and_opened(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Sardaigne en famille',
            'departure_port' => 'Bonifacio',
            'destination_port' => 'La Maddalena',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 7,
            'auto_duration' => false,
            'crew_count' => 4,
            'navigation_style' => 'Famille',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cruises', [
            'title' => 'Sardaigne en famille',
            'departure_port' => 'Bonifacio',
        ]);

        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_a_brittany_cruise_receives_stops_and_a_budget(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Tour de Bretagne',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Vannes',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 15,
            'auto_duration' => false,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ]);

        $this->get($response->headers->get('Location'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('demo/cruise-show')
                ->where('cruise.stops', 7)
                ->where('cruise.miles', 273)
                ->where('cruise.legs.0.to', 'Saint-Quay-Portrieux')
                ->where('cruise.legs.2.overnight', 'Mouillage')
                ->where('cruise.budget.total', 584)
            );
    }

    public function test_a_created_cruise_can_be_deleted(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Week-end côtier',
            'departure_port' => 'Lorient',
            'destination_port' => 'Vannes',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 3,
            'auto_duration' => false,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ]);

        $this->delete($response->headers->get('Location'))
            ->assertRedirect('/croisieres');

        $this->assertDatabaseMissing('cruises', [
            'title' => 'Week-end côtier',
        ]);
        $this->get('/croisieres')->assertOk();
    }

    public function test_a_duration_can_be_calculated_automatically(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Tour de Bretagne automatique',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Vannes',
            'departure_date' => now()->addWeek()->toDateString(),
            'auto_duration' => true,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cruises', ['title' => 'Tour de Bretagne automatique', 'duration_days' => 15]);
    }

    public function test_automatic_duration_depends_on_navigation_style(): void
    {
        $this->getJson('/api/cruises/duration?departure_port=Saint-Malo&destination_port=Québec&navigation_style=Rapide')
            ->assertOk()
            ->assertJsonPath('days', 24);

        $this->getJson('/api/cruises/duration?departure_port=Saint-Malo&destination_port=Québec&navigation_style=Famille')
            ->assertOk()
            ->assertJsonPath('days', 36);
    }

    public function test_explorer_selection_prefills_a_new_cruise(): void
    {
        $this->get('/croisieres/nouvelle?departure=Saint-Malo&destination=Québec')
            ->assertInertia(fn (Assert $page) => $page
                ->component('cruises/create')
                ->where('prefill.departure_port', 'Saint-Malo')
                ->where('prefill.destination_port', 'Québec')
            );
    }

    public function test_a_quebec_passage_has_a_transatlantic_plan(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Saint-Malo Québec',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Québec',
            'departure_date' => now()->addWeek()->toDateString(),
            'auto_duration' => true,
            'crew_count' => 3,
            'navigation_style' => 'Confort',
        ]);

        $this->get($response->headers->get('Location'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('cruise.miles', 3330)
                ->where('cruise.scenarios.0.days', 28)
                ->where('cruise.legs.0.to', 'Horta')
                ->where('cruise.legs.1.to', 'Halifax')
            );
    }

    public function test_a_known_generic_route_receives_a_maritime_distance(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Saint-Malo Marseille',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Marseille',
            'departure_date' => now()->addWeek()->toDateString(),
            'auto_duration' => true,
            'crew_count' => 3,
            'navigation_style' => 'Confort',
        ]);

        $this->get($response->headers->get('Location'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('cruise.scenarios.0.label', 'Route maritime calculée')
                ->where('cruise.scenarios.0.stops', 0)
                ->where('cruise.miles', fn (int $miles) => $miles > 0)
                ->where('cruise.route_geometry.0.latitude', fn (float $latitude) => $latitude > 40)
            );
    }

    public function test_an_existing_cruise_can_be_edited_and_recalculated(): void
    {
        $response = $this->post('/croisieres', [
            'title' => 'Saint-Malo Québec à corriger',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Québec',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 7,
            'auto_duration' => false,
            'crew_count' => 3,
            'navigation_style' => 'Confort',
        ]);

        $this->put($response->headers->get('Location'), [
            'title' => 'Saint-Malo Québec à corriger',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Québec',
            'departure_date' => now()->addWeek()->toDateString(),
            'auto_duration' => true,
            'crew_count' => 3,
            'navigation_style' => 'Confort',
        ])->assertRedirect();

        $this->assertDatabaseHas('cruises', ['title' => 'Saint-Malo Québec à corriger', 'duration_days' => 28]);
    }
}
