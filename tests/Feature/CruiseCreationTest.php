<?php

namespace Tests\Feature;

use App\Models\Cruise;
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

    public function test_a_cruise_page_opens_without_a_computed_route(): void
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

        // Les itinéraires ne sont calculés que sur demande explicite.
        $this->get($response->headers->get('Location'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('cruises/show')
                ->where('cruise.stops', 0)
                ->where('cruise.route_available', false)
                ->has('scenarios', 0)
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

        // La durée est calculée sur la distance maritime réelle, pas saisie.
        $cruise = Cruise::where('title', 'Tour de Bretagne automatique')->firstOrFail();
        $this->assertGreaterThan(1, $cruise->duration_days);
        $this->assertLessThan(30, $cruise->duration_days);
    }

    public function test_automatic_duration_depends_on_navigation_style(): void
    {
        $fast = $this->getJson('/api/cruises/duration?departure_port=Saint-Malo&destination_port=Québec&navigation_style=Rapide')
            ->assertOk()
            ->json('days');

        $family = $this->getJson('/api/cruises/duration?departure_port=Saint-Malo&destination_port=Québec&navigation_style=Famille')
            ->assertOk()
            ->json('days');

        // Une traversée transatlantique se compte en semaines, et le style
        // « Famille » impose des journées plus courtes que « Rapide ».
        $this->assertGreaterThan(14, $fast);
        $this->assertGreaterThan($fast, $family);
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

    public function test_a_transatlantic_passage_is_measured_in_weeks(): void
    {
        $this->post('/croisieres', [
            'title' => 'Saint-Malo Québec',
            'departure_port' => 'Saint-Malo',
            'destination_port' => 'Québec',
            'departure_date' => now()->addWeek()->toDateString(),
            'auto_duration' => true,
            'crew_count' => 3,
            'navigation_style' => 'Confort',
        ])->assertRedirect();

        $cruise = Cruise::where('title', 'Saint-Malo Québec')->firstOrFail();

        // La traversée dépasse largement une croisière côtière.
        $this->assertGreaterThan(20, $cruise->duration_days);
    }

    public function test_a_coastal_route_is_shorter_than_an_ocean_crossing(): void
    {
        foreach ([
            ['Marseille Bonifacio', 'Marseille', 'Bonifacio'],
            ['Saint-Malo Québec bis', 'Saint-Malo', 'Québec'],
        ] as [$title, $departure, $destination]) {
            $this->post('/croisieres', [
                'title' => $title,
                'departure_port' => $departure,
                'destination_port' => $destination,
                'departure_date' => now()->addWeek()->toDateString(),
                'auto_duration' => true,
                'crew_count' => 3,
                'navigation_style' => 'Confort',
            ])->assertRedirect();
        }

        $coastal = Cruise::where('title', 'Marseille Bonifacio')->firstOrFail();
        $ocean = Cruise::where('title', 'Saint-Malo Québec bis')->firstOrFail();

        $this->assertLessThan($ocean->duration_days, $coastal->duration_days);
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

        // La durée saisie (7 jours) est remplacée par l'estimation calculée.
        $cruise = Cruise::where('title', 'Saint-Malo Québec à corriger')->firstOrFail();
        $this->assertGreaterThan(7, $cruise->duration_days);
    }
}
