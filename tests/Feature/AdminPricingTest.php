<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/tarifs')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/tarifs')
            ->assertForbidden();
    }

    public function test_organization_owner_is_not_a_platform_admin(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Flotte test',
            'type' => 'business',
            'owner_id' => $user->id,
        ]);
        $organization->members()->attach($user->id, ['role' => 'owner']);

        $this->actingAs($user)
            ->get('/admin/tarifs')
            ->assertForbidden();
    }

    public function test_admin_pricing_page_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $feature = PlanFeature::create([
            'name' => 'Navigation hauturiere',
            'slug' => 'offshore-navigation',
            'category' => 'navigation',
        ]);
        Plan::create([
            'name' => 'Hauturier',
            'slug' => 'hauturier',
            'audience' => 'personal',
            'monthly_price_cents' => 1900,
        ])->features()->attach($feature);

        $this->actingAs($admin)->get('/admin/tarifs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/pricing')
                ->where('plans.0.name', 'Hauturier')
                ->where('features.0.slug', 'offshore-navigation')
            );
    }

    public function test_admin_dashboard_and_users_page_are_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('stats.users', 2)
                ->has('recentUsers', 2)
            );

        $this->actingAs($admin)
            ->get('/admin/utilisateurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users')
                ->has('users', 2)
            );
    }

    public function test_admin_can_promote_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/utilisateurs/{$user->id}/role", ['role' => 'admin'])
            ->assertRedirect('/admin/utilisateurs');

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_admin_cannot_remove_their_own_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from('/admin/utilisateurs')
            ->put("/admin/utilisateurs/{$admin->id}/role", ['role' => 'user'])
            ->assertRedirect('/admin/utilisateurs')
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_update_a_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $feature = PlanFeature::create([
            'name' => 'Gestion de flotte',
            'slug' => 'fleet-management',
            'category' => 'pro',
        ]);
        $plan = Plan::create([
            'name' => 'Loueur Pro',
            'slug' => 'loueur-pro',
            'audience' => 'business',
            'monthly_price_cents' => 4900,
        ]);

        $this->actingAs($admin)->put("/admin/tarifs/{$plan->id}", [
            'name' => 'Loueur Premium',
            'audience' => 'business',
            'monthly_price_cents' => 5900,
            'yearly_price_cents' => 59000,
            'boat_limit' => null,
            'member_limit' => null,
            'is_active' => true,
            'feature_ids' => [$feature->id],
        ])->assertRedirect('/admin/tarifs');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Loueur Premium',
            'monthly_price_cents' => 5900,
        ]);
        $this->assertTrue($plan->fresh()->features()->where('slug', 'fleet-management')->exists());
    }
}
