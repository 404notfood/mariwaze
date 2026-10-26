<?php

namespace Tests\Feature;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Port;
use App\Models\User;
use App\Services\Routing\BoatCompatibilityService;
use App\Services\Routing\StopFinder;
use App\Support\GeoScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaritimePlacesTest extends TestCase
{
    use RefreshDatabase;

    private function marseille(): array
    {
        return ['latitude' => 43.296, 'longitude' => 5.370];
    }

    public function test_nearby_search_returns_places_within_the_radius(): void
    {
        $near = Port::create([
            'slug' => 'vieux-port', 'name' => 'Vieux-Port', 'latitude' => 43.295, 'longitude' => 5.374,
            'source' => 'test', 'source_ref' => 'near',
        ]);

        // Nice est à plus de 100 milles nautiques de Marseille.
        Port::create([
            'slug' => 'nice', 'name' => 'Nice', 'latitude' => 43.695, 'longitude' => 7.271,
            'source' => 'test', 'source_ref' => 'far',
        ]);

        $found = app(StopFinder::class)->portsNear(43.296, 5.370, 10);

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($near));
    }

    public function test_nautical_distance_matches_known_reference(): void
    {
        // Marseille → Nice : environ 95 milles nautiques à vol d'oiseau.
        $distance = GeoScope::nauticalMiles(43.296, 5.370, 43.695, 7.271);

        $this->assertGreaterThan(80, $distance);
        $this->assertLessThan(110, $distance);
    }

    public function test_a_deep_draft_boat_is_incompatible_with_a_shallow_port(): void
    {
        $port = Port::create([
            'slug' => 'port-peu-profond', 'name' => 'Port peu profond',
            'latitude' => 43.0, 'longitude' => 5.0, 'min_depth_meters' => 1.2,
            'source' => 'test', 'source_ref' => 'shallow',
        ]);

        $boat = new Boat(['name' => 'Quillard', 'boat_type' => 'voilier', 'length' => 12.0, 'width' => 4.0, 'draft' => 2.5, 'avg_speed' => 6.0]);

        $check = app(BoatCompatibilityService::class)->forPort($boat, $port);

        $this->assertFalse($check['compatible']);
        $this->assertNotEmpty($check['reasons']);
    }

    public function test_a_shallow_draft_boat_is_compatible(): void
    {
        $port = Port::create([
            'slug' => 'port-profond', 'name' => 'Port profond',
            'latitude' => 43.0, 'longitude' => 5.0, 'min_depth_meters' => 4.0,
            'source' => 'test', 'source_ref' => 'deep',
        ]);

        $boat = new Boat(['name' => 'Dériveur', 'boat_type' => 'deriveur', 'length' => 8.0, 'width' => 3.0, 'draft' => 0.8, 'avg_speed' => 6.0]);

        $check = app(BoatCompatibilityService::class)->forPort($boat, $port);

        $this->assertTrue($check['compatible']);
        $this->assertSame(100, $check['score']);
    }

    public function test_the_explore_page_lists_real_places(): void
    {
        $this->actingAs(User::factory()->create());

        Port::create([
            'slug' => 'port-test', 'name' => 'Port de test', 'latitude' => 43.0, 'longitude' => 5.0,
            'source' => 'test', 'source_ref' => 'p1',
        ]);
        Anchorage::create([
            'slug' => 'mouillage-test', 'name' => 'Mouillage de test', 'latitude' => 43.1, 'longitude' => 5.1,
            'source' => 'test', 'source_ref' => 'a1',
        ]);

        $this->get('/explorer')
            ->assertInertia(fn (Assert $page) => $page
                ->component('explore')
                ->where('counts.ports', 1)
                ->where('counts.anchorages', 1)
                ->has('spots', 2)
            );
    }

    public function test_an_anchorage_page_shows_its_reviews(): void
    {
        $user = User::factory()->create(['name' => 'Capitaine Test']);
        $this->actingAs($user);
        $anchorage = Anchorage::create([
            'slug' => 'calanque-test', 'name' => 'Calanque de test', 'latitude' => 43.2, 'longitude' => 5.4,
            'source' => 'test', 'source_ref' => 'a2',
        ]);

        $anchorage->reviews()->create([
            'user_id' => $user->id,
            'rating' => 4,
            'body' => 'Bonne tenue au mistral.',
            'status' => 'approved',
        ]);

        $this->get('/mouillages/calanque-test')
            ->assertInertia(fn (Assert $page) => $page
                ->component('anchorage-show')
                ->where('anchorage.name', 'Calanque de test')
                ->where('anchorage.reviews_count', 1)
                ->where('anchorage.reviews.0.author', 'Capitaine Test')
                ->where('anchorage.rating', fn (int|float $rating): bool => (float) $rating === 4.0)
            );
    }

    public function test_a_signed_in_user_can_review_an_anchorage(): void
    {
        $user = User::factory()->create();
        Anchorage::create([
            'slug' => 'baie-test', 'name' => 'Baie de test', 'latitude' => 43.3, 'longitude' => 5.5,
            'source' => 'test', 'source_ref' => 'a3',
        ]);

        $this->actingAs($user)
            ->post('/mouillages/baie-test/avis', ['rating' => 5, 'body' => 'Superbe mouillage.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_reviewing_requires_authentication(): void
    {
        Anchorage::create([
            'slug' => 'anse-test', 'name' => 'Anse de test', 'latitude' => 43.4, 'longitude' => 5.6,
            'source' => 'test', 'source_ref' => 'a4',
        ]);

        $this->post('/mouillages/anse-test/avis', ['rating' => 5])
            ->assertRedirect('/login');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_reviewing_requires_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        Anchorage::create([
            'slug' => 'anse-verification', 'name' => 'Anse vérification', 'latitude' => 43.4, 'longitude' => 5.6,
            'source' => 'test', 'source_ref' => 'a5',
        ]);

        $this->actingAs($user)
            ->post('/mouillages/anse-verification/avis', ['rating' => 5])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_the_stop_finder_ranks_anchorages_by_compatibility(): void
    {
        Anchorage::create([
            'slug' => 'peu-profond', 'name' => 'Peu profond', 'latitude' => 43.296, 'longitude' => 5.371,
            'min_depth_meters' => 1.0, 'source' => 'test', 'source_ref' => 'c1',
        ]);
        Anchorage::create([
            'slug' => 'bien-profond', 'name' => 'Bien profond', 'latitude' => 43.297, 'longitude' => 5.372,
            'min_depth_meters' => 6.0, 'source' => 'test', 'source_ref' => 'c2',
        ]);

        $boat = new Boat(['name' => 'Test', 'boat_type' => 'voilier', 'length' => 12.0, 'width' => 4.0, 'draft' => 2.0, 'avg_speed' => 6.0]);

        $results = app(StopFinder::class)->compatibleAnchorages($boat, 43.296, 5.370, 10, 5);

        $this->assertNotEmpty($results);
        $this->assertSame('Bien profond', $results[0]['model']->name);
        $this->assertTrue($results[0]['compatible']);
    }
}
