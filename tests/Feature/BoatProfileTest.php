<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoatProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_boat_profile_can_be_saved_and_displayed(): void
    {
        $this->post('/bateau', [
            'name' => 'Éole',
            'model' => 'Dufour 390',
            'boat_type' => 'voilier',
            'flag' => 'France',
            'mmsi' => '227123456',
            'length' => 11.94,
            'width' => 3.99,
            'draft' => 1.95,
            'mast_height' => 17.7,
            'avg_speed' => 6.5,
            'fuel_range' => 180,
        ])->assertRedirect('/bateau');

        $this->assertDatabaseHas('boats', [
            'name' => 'Éole',
            'model' => 'Dufour 390',
            'mmsi' => '227123456',
        ]);

        $this->get('/bateau')->assertInertia(fn (Assert $page) => $page
            ->component('boat-show')
            ->where('boat.name', 'Éole')
            ->where('boat.draft', 1.95)
            ->where('boat.mast_height', 17.7)
        );
    }
}
