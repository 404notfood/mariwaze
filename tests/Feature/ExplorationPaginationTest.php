<?php

namespace Tests\Feature;

use App\Models\Anchorage;
use App\Models\Port;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class ExplorationPaginationTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_all_mixed_places_remain_reachable_in_a_stable_order(): void
    {
        $slugs = [];
        foreach (range(1, 37) as $index) {
            $attributes = ['name' => sprintf('Escale %03d', $index), 'latitude' => 43, 'longitude' => 5];
            Port::create($attributes + ['slug' => 'port-'.$index]);
            Anchorage::create($attributes + ['slug' => 'anchorage-'.$index]);
            $slugs[] = 'anchorage-'.$index;
            $slugs[] = 'port-'.$index;
        }

        foreach (range(1, 4) as $number) {
            $expected = array_slice($slugs, ($number - 1) * 24, 24);
            $this->get('/explorer?page='.$number)->assertInertia(fn (Assert $page) => $page
                ->where('pagination.total', 74)
                ->where('pagination.current_page', $number)
                ->where('pagination.last_page', 4)
                ->where('pagination.from', ($number - 1) * 24 + 1)
                ->where('pagination.to', min($number * 24, 74))
                ->where('spots', fn ($spots) => collect($spots)->pluck('slug')->all() === $expected));
        }
    }

    public function test_pagination_counts_only_matching_places_and_keeps_filters_in_links(): void
    {
        foreach (range(1, 26) as $index) {
            Port::create(['slug' => 'filtered-'.$index, 'name' => sprintf('Port %03d', $index), 'latitude' => 43, 'longitude' => 5, 'has_water' => true, 'min_depth_meters' => 5]);
        }
        Port::create(['slug' => 'no-water', 'name' => 'Port sans eau', 'latitude' => 43, 'longitude' => 5, 'min_depth_meters' => 5]);
        Port::create(['slug' => 'shallow', 'name' => 'Port peu profond', 'latitude' => 43, 'longitude' => 5, 'has_water' => true, 'min_depth_meters' => 1]);
        Anchorage::create(['slug' => 'anchorage', 'name' => 'Port mouillage', 'latitude' => 43, 'longitude' => 5, 'min_depth_meters' => 5]);

        $response = $this->get('/explorer?q=Port&service=has_water&min_depth=3');
        $response->assertInertia(fn (Assert $page) => $page
            ->has('spots', 24)
            ->where('pagination.total', 26)
            ->where('pagination.previous_url', null)
            ->where('spots.0.services', ['Eau']));
        $next = $response->viewData('page')['props']['pagination']['next_url'];
        parse_str(parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame(['q' => 'Port', 'service' => 'has_water', 'min_depth' => '3', 'page' => '2'], $query);
        $this->get($next)->assertInertia(fn (Assert $page) => $page
            ->has('spots', 2)
            ->where('pagination.total', 26)
            ->where('pagination.next_url', null));
    }

    public function test_empty_catalogue_has_no_pagination_links(): void
    {
        $this->get('/explorer')->assertInertia(fn (Assert $page) => $page
            ->has('spots', 0)
            ->where('pagination.total', 0)
            ->where('pagination.from', null)
            ->where('pagination.to', null)
            ->where('pagination.previous_url', null)
            ->where('pagination.next_url', null));
    }

    public function test_invalid_page_numbers_are_rejected(): void
    {
        foreach (['0', '-1', '1.5', 'abc', '1000001'] as $number) {
            $this->getJson('/explorer?page='.$number)->assertUnprocessable()->assertJsonValidationErrors('page');
        }
    }
}
