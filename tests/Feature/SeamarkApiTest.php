<?php

namespace Tests\Feature;

use App\Models\Seamark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class SeamarkApiTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_seamarks_are_returned_within_a_small_viewport(): void
    {
        Seamark::create([
            'source_ref' => 'node/1',
            'name' => 'Cardinale des Pierres Noires',
            'category' => 'cardinal_west',
            'seamark_type' => 'buoy_cardinal',
            'latitude' => 48.30,
            'longitude' => -4.90,
        ]);
        Seamark::create([
            'source_ref' => 'node/2',
            'name' => 'Hors zone',
            'category' => 'lighthouse',
            'seamark_type' => 'light_major',
            'latitude' => 43.0,
            'longitude' => 5.0,
        ]);

        $this->getJson('/api/seamarks?south=48.0&north=48.6&west=-5.2&east=-4.5')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['category' => 'cardinal_west', 'label' => 'Cardinale ouest']);
    }

    public function test_a_viewport_too_wide_returns_nothing(): void
    {
        Seamark::create([
            'source_ref' => 'node/1',
            'name' => null,
            'category' => 'lighthouse',
            'seamark_type' => 'light_major',
            'latitude' => 48.3,
            'longitude' => -4.9,
        ]);

        $this->getJson('/api/seamarks?south=40.0&north=52.0&west=-10.0&east=10.0')
            ->assertOk()
            ->assertExactJson([]);
    }
}
