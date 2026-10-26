<?php

namespace Tests\Feature;

use App\Jobs\GenerateCruiseRoutes;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\Organization;
use App\Models\User;
use App\Services\Accounts\AccountContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AccountIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_maritime_pages_require_a_verified_account(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->actingAs(User::factory()->unverified()->create())
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_an_account_cannot_access_or_mutate_another_accounts_cruise(): void
    {
        $owner = User::factory()->create();
        $organization = app(AccountContext::class)->currentOrCreate($owner);
        $cruise = Cruise::create([
            'user_id' => $owner->id,
            'organization_id' => $organization->id,
            'slug' => 'privee-secret',
            'title' => 'Croisière privée',
            'departure_port' => 'Brest',
            'destination_port' => 'Lorient',
            'departure_date' => now()->addWeek(),
            'duration_days' => 4,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ]);

        $intruder = User::factory()->create();
        $this->actingAs($intruder);

        $this->get("/croisieres/{$cruise->slug}")->assertNotFound();
        $this->get("/croisieres/{$cruise->slug}/modifier")->assertNotFound();
        $this->put("/croisieres/{$cruise->slug}", $this->validCruisePayload())->assertNotFound();
        $this->post("/croisieres/{$cruise->slug}/itineraires")->assertNotFound();
        $this->put("/croisieres/{$cruise->slug}/itineraires/direct")->assertNotFound();
        $this->delete("/croisieres/{$cruise->slug}")->assertNotFound();

        $this->assertDatabaseHas('cruises', ['id' => $cruise->id, 'title' => 'Croisière privée']);
    }

    public function test_a_cruise_cannot_reference_a_boat_from_another_account(): void
    {
        $owner = User::factory()->create();
        $ownerOrganization = app(AccountContext::class)->currentOrCreate($owner);
        $boat = Boat::create([
            'user_id' => $owner->id,
            'organization_id' => $ownerOrganization->id,
            'name' => 'Bateau privé',
            'boat_type' => 'voilier',
            'length' => 10,
            'width' => 3,
            'draft' => 1.5,
            'avg_speed' => 6,
        ]);

        $intruder = User::factory()->create();
        $this->actingAs($intruder)
            ->from('/croisieres/nouvelle')
            ->post('/croisieres', [...$this->validCruisePayload(), 'boat_id' => $boat->id])
            ->assertRedirect('/croisieres/nouvelle')
            ->assertSessionHasErrors('boat_id');

        $this->assertDatabaseCount('cruises', 0);
    }

    public function test_expensive_api_endpoints_require_authentication(): void
    {
        $this->getJson('/api/routes/preview?from_latitude=48&from_longitude=-4&to_latitude=47&to_longitude=-3')
            ->assertUnauthorized();
        $this->getJson('/api/marine/conditions?latitude=48&longitude=-4')
            ->assertUnauthorized();
    }

    public function test_read_only_members_can_view_but_cannot_mutate_fleet_data(): void
    {
        $owner = User::factory()->create();
        $organization = app(AccountContext::class)->currentOrCreate($owner);
        $cruise = $this->cruiseFor($owner, $organization);
        $member = User::factory()->create();
        $organization->members()->attach($member->id, ['role' => 'viewer']);

        $this->actingAs($member)
            ->get("/croisieres/{$cruise->slug}")
            ->assertOk();
        $this->get("/croisieres/{$cruise->slug}/modifier")->assertForbidden();
        $this->post("/croisieres/{$cruise->slug}/itineraires")->assertForbidden();
        $this->delete("/croisieres/{$cruise->slug}")->assertForbidden();
        $this->post('/bateaux', $this->validBoatPayload())->assertForbidden();
    }

    public function test_manager_can_manage_organization_cruises(): void
    {
        $owner = User::factory()->create();
        $organization = app(AccountContext::class)->currentOrCreate($owner);
        $cruise = $this->cruiseFor($owner, $organization);
        $manager = User::factory()->create();
        $organization->members()->attach($manager->id, ['role' => 'manager']);

        $this->actingAs($manager)
            ->delete("/croisieres/{$cruise->slug}")
            ->assertRedirect('/croisieres');

        $this->assertDatabaseMissing('cruises', ['id' => $cruise->id]);
    }

    public function test_personal_organization_creation_is_idempotent(): void
    {
        $user = User::factory()->create();
        $accounts = app(AccountContext::class);

        $first = $accounts->currentOrCreate($user);
        $second = $accounts->currentOrCreate($user);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('organizations', 1);
    }

    public function test_a_second_route_generation_is_not_queued_while_one_is_active(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $organization = app(AccountContext::class)->currentOrCreate($owner);
        $cruise = $this->cruiseFor($owner, $organization);

        $this->actingAs($owner)
            ->post("/croisieres/{$cruise->slug}/itineraires")
            ->assertRedirect();
        $this->post("/croisieres/{$cruise->slug}/itineraires")
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertPushed(GenerateCruiseRoutes::class, 1);
    }

    private function cruiseFor(User $owner, Organization $organization): Cruise
    {
        return Cruise::create([
            'user_id' => $owner->id,
            'organization_id' => $organization->id,
            'slug' => 'croisiere-equipe-'.strtolower((string) str()->uuid()),
            'title' => 'Croisière équipe',
            'departure_port' => 'Brest',
            'destination_port' => 'Lorient',
            'departure_date' => now()->addWeek(),
            'duration_days' => 4,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ]);
    }

    /** @return array<string, mixed> */
    private function validBoatPayload(): array
    {
        return [
            'name' => 'Bateau équipe',
            'boat_type' => 'voilier',
            'length' => 10,
            'width' => 3,
            'draft' => 1.5,
            'avg_speed' => 6,
        ];
    }

    /** @return array<string, mixed> */
    private function validCruisePayload(): array
    {
        return [
            'title' => 'Nouvelle croisière',
            'departure_port' => 'Brest',
            'destination_port' => 'Lorient',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 4,
            'auto_duration' => false,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
        ];
    }
}
