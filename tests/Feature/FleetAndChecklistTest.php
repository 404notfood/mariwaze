<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\ChecklistTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetAndChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_boat_is_attached_to_their_personal_organization(): void
    {
        $user = User::factory()->create(['name' => 'Loueur Atlantique']);

        $this->actingAs($user)->post('/bateau', [
            'name' => 'Avel Vor',
            'model' => 'Lagoon 42',
            'boat_type' => 'catamaran',
            'flag' => 'France',
            'mmsi' => '227123457',
            'length' => 12.8,
            'width' => 7.7,
            'draft' => 1.25,
            'mast_height' => 20.65,
            'avg_speed' => 7,
            'fuel_range' => 300,
            'fleet_status' => 'available',
            'berths' => 8,
            'fuel_consumption_per_hour' => 6.5,
        ])->assertRedirect('/bateau');

        $organization = Organization::query()->where('owner_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('boats', [
            'name' => 'Avel Vor',
            'organization_id' => $organization->id,
            'berths' => 8,
        ]);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_a_cruise_can_use_a_fleet_boat_and_generates_an_inland_checklist(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create(['name' => 'Canal Charter', 'type' => 'business', 'owner_id' => $user->id]);
        $organization->members()->attach($user->id, ['role' => 'owner']);
        $boat = Boat::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'name' => 'Canal One',
            'boat_type' => 'moteur',
            'length' => 9,
            'width' => 3,
            'draft' => 0.8,
            'avg_speed' => 6,
            'fuel_range' => 180,
            'fleet_status' => 'available',
            'fuel_consumption_per_hour' => 4.5,
        ]);
        $template = ChecklistTemplate::create(['name' => 'Fluvial', 'navigation_area' => 'inland']);
        $section = $template->sections()->create(['name' => 'Avitaillement', 'position' => 1]);
        $section->items()->create(['label' => 'Eau potable', 'position' => 1]);
        $section->items()->create(['label' => 'Carburant', 'position' => 2]);

        $response = $this->actingAs($user)->post('/croisieres', [
            'title' => 'Canal du Midi',
            'departure_port' => 'Toulouse',
            'destination_port' => 'Sete',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 5,
            'auto_duration' => false,
            'crew_count' => 4,
            'navigation_style' => 'Confort',
            'navigation_area' => 'inland',
            'boat_id' => $boat->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cruises', [
            'title' => 'Canal du Midi',
            'organization_id' => $organization->id,
            'boat_id' => $boat->id,
            'navigation_area' => 'inland',
        ]);
        $this->assertDatabaseHas('cruise_checklist_items', [
            'section' => 'Avitaillement',
            'label' => 'Eau potable',
            'quantity' => '60 L',
        ]);
        $this->assertDatabaseHas('cruise_checklist_items', [
            'label' => 'Carburant',
            'quantity' => '90 L',
        ]);
    }
}
