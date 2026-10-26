<?php

namespace Tests\Feature;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\CruiseChecklist;
use App\Models\Port;
use App\Models\User;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Navigation\PortCoordinates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class TravelImprovementsTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_homonymous_places_are_resolved_by_reference(): void
    {
        $first = Port::create(['slug' => 'port-fr', 'name' => 'Port Test', 'country_code' => 'FR', 'latitude' => 43, 'longitude' => 5]);
        $second = Port::create(['slug' => 'port-ca', 'name' => 'Port Test', 'country_code' => 'CA', 'latitude' => 46, 'longitude' => -71]);
        $this->getJson('/api/places?q=Port%20Test')->assertOk()->assertJsonCount(2, 'places')->assertJsonPath('places.1.country_code', 'CA');
        $this->assertSame(-71.0, app(PortCoordinates::class)->find('Port Test', 'port:'.$second->id)['longitude']);
        $response = $this->post('/croisieres', $this->payload() + ['departure_place' => 'port:'.$first->id]);
        $response->assertSessionHasErrors('departure_port');
        $this->post('/croisieres', array_replace($this->payload(), ['departure_port' => 'Port Test', 'departure_place' => 'port:'.$second->id]))->assertRedirect();
        $this->assertDatabaseHas('cruises', ['departure_place' => 'port:'.$second->id]);
    }

    public function test_exploration_filters_depth_services_and_protection_before_limiting(): void
    {
        foreach (range(1, 65) as $index) {
            Port::create(['slug' => 'shallow-'.$index, 'name' => 'A port '.$index, 'latitude' => 43, 'longitude' => 5, 'min_depth_meters' => 1]);
        }
        Port::create(['slug' => 'deep', 'name' => 'Z port profond', 'latitude' => 43, 'longitude' => 5, 'min_depth_meters' => 5, 'has_water' => true]);
        Port::create(['slug' => 'unknown', 'name' => 'Fond inconnu', 'latitude' => 43, 'longitude' => 5, 'has_water' => true]);
        $this->get('/explorer?min_depth=3&service=has_water')->assertInertia(fn (Assert $page) => $page->has('spots', 1)->where('spots.0.slug', 'deep'));
        Anchorage::create(['slug' => 'shelter', 'name' => 'Abri', 'latitude' => 43, 'longitude' => 5, 'protected_from' => ['N', 'NE']]);
        $this->get('/explorer?protection=N')->assertInertia(fn (Assert $page) => $page->has('spots', 1)->where('spots.0.slug', 'shelter'));
        $this->get('/explorer?protection=S')->assertInertia(fn (Assert $page) => $page->has('spots', 0));
        $this->get('/explorer?boat_id=99999')->assertUnprocessable();
    }

    public function test_boat_filter_enforces_depth_margin_and_known_dimensions(): void
    {
        $boat = Boat::create(['organization_id' => $this->accountOrganization->id, 'name' => 'Test', 'model' => 'Test', 'boat_type' => 'sailboat', 'length' => 12, 'width' => 4, 'draft' => 2, 'avg_speed' => 6]);
        foreach ([['ok', 3, 15], ['margin', 2.2, 15], ['short', 3, 10], ['unknown', null, 15]] as [$slug, $depth, $length]) {
            Port::create(['slug' => $slug, 'name' => $slug, 'latitude' => 43, 'longitude' => 5, 'min_depth_meters' => $depth, 'max_length_meters' => $length]);
        }
        $this->get('/explorer?boat_id='.$boat->id)->assertInertia(fn (Assert $page) => $page->has('spots', 1)->where('spots.0.slug', 'ok'));
    }

    public function test_booklet_gpx_and_checklist_use_only_the_owned_cruise(): void
    {
        $this->mock(CruiseDurationEstimator::class)->shouldReceive('estimate')->andReturn(2);
        $this->post('/croisieres', $this->payload())->assertRedirect();
        $cruise = Cruise::firstOrFail();
        $this->get('/croisieres/'.$cruise->slug.'/export.gpx')->assertUnprocessable();
        $cruise->routes()->create(['scenario' => 'comfort', 'label' => 'Confort', 'is_selected' => true, 'geometry' => [['latitude' => 43, 'longitude' => 5], ['latitude' => 44, 'longitude' => 6]], 'computed_at' => now()]);
        $xml = $this->get('/croisieres/'.$cruise->slug.'/export.gpx')->assertOk()->assertHeader('Content-Type', 'application/gpx+xml; charset=UTF-8')->getContent();
        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document);
        $this->assertCount(2, $document->trk->trkseg->trkpt);
        $this->assertSame('5', (string) $document->trk->trkseg->trkpt[0]['lon']);
        $item = CruiseChecklist::where('cruise_id', $cruise->id)->firstOrFail()->items()->firstOrFail();
        $this->patch('/croisieres/'.$cruise->slug.'/checklist/'.$item->id, ['checked' => true])->assertRedirect();
        $this->assertTrue($item->fresh()->checked);
        $html = $this->get('/croisieres/'.$cruise->slug.'/carnet')->assertOk()->assertSee('sans connexion')->assertSee('checked', false)->getContent();
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<link', $html);
        $this->actingAs(User::factory()->create());
        $this->get('/croisieres/'.$cruise->slug.'/carnet')->assertNotFound();
        $this->get('/croisieres/'.$cruise->slug.'/export.gpx')->assertNotFound();
        $this->patch('/croisieres/'.$cruise->slug.'/checklist/'.$item->id, ['checked' => false])->assertNotFound();
    }

    public function test_editing_a_trip_invalidates_exports_and_refreshes_quantities_without_losing_checks(): void
    {
        $this->post('/croisieres', $this->payload())->assertRedirect();
        $cruise = Cruise::firstOrFail();
        $cruise->routes()->create(['scenario' => 'comfort', 'label' => 'Confort', 'is_selected' => true, 'geometry' => [['latitude' => 43, 'longitude' => 5], ['latitude' => 44, 'longitude' => 6]]]);
        $item = CruiseChecklist::where('cruise_id', $cruise->id)->firstOrFail()->items()->where('label', 'Eau potable')->firstOrFail();
        $item->update(['checked' => true]);
        $this->put('/croisieres/'.$cruise->slug, array_replace($this->payload(), ['duration_days' => 10, 'crew_count' => 3]))->assertRedirect();
        $this->get('/croisieres/'.$cruise->slug.'/export.gpx')->assertUnprocessable();
        $this->assertTrue($item->fresh()->checked);
        $this->assertSame('90 L', $item->fresh()->quantity);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['title' => 'Carnet <test> & navigation', 'departure_port' => 'Marseille', 'destination_port' => 'Bonifacio', 'departure_date' => now()->addWeek()->toDateString(), 'duration_days' => 7, 'auto_duration' => false, 'crew_count' => 2, 'navigation_style' => 'Confort'];
    }
}
