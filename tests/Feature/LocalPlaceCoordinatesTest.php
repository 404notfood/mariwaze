<?php

namespace Tests\Feature;

use App\Models\PlaceCoordinate;
use App\Services\Navigation\PortCoordinates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalPlaceCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_local_geodata_record_is_resolved_without_an_external_api(): void
    {
        PlaceCoordinate::query()->create([
            'name' => 'Dinan',
            'normalized_name' => 'dinan',
            'latitude' => 48.455,
            'longitude' => -2.048,
            'feature_class' => 'P',
            'feature_code' => 'PPL',
            'country_code' => 'FR',
        ]);

        $location = app(PortCoordinates::class)->find('Dinan');

        $this->assertSame('Dinan', $location['name']);
        $this->assertSame(48.455, $location['latitude']);
        $this->assertSame(-2.048, $location['longitude']);
    }
}
