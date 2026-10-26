<?php

namespace Tests\Feature;

use App\Models\Anchorage;
use App\Models\PlaceCoordinate;
use App\Models\Port;
use App\Models\Seamark;
use App\Services\Import\WorldCatalogueImporter;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\PortGeocoder;
use App\Services\Routing\StopFinder;
use App\Services\Safety\SafetyAuditor;
use Database\Seeders\MaritimeSafetySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class WorldCatalogueTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_world_import_is_repeatable_and_supports_translated_city_names(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mariwaze-cities-');
        file_put_contents($file, implode("\t", ['101', 'Lisbon', 'Lisbon', 'Lisbonne,Lisboa', '38.72509', '-9.1498', 'P', 'PPLC', 'PT', '', '14', '', '', '', '545000', '', '0', 'Europe/Lisbon', '2026-10-26'])."\n");
        try {
            $importer = app(WorldCatalogueImporter::class);
            $this->assertSame(1, $importer->cities($file));
            $id = PlaceCoordinate::where('geoname_id', 101)->value('id');
            $this->assertSame(1, $importer->cities($file));
            $this->assertSame($id, PlaceCoordinate::where('geoname_id', 101)->value('id'));
            $this->assertDatabaseCount('place_coordinates', 1);
            $this->mock(PortGeocoder::class)->shouldNotReceive('locate');
            $this->assertSame(-9.1498, app(PortCoordinates::class)->find('Lisbonne')['longitude']);
            $this->getJson('/api/places?q=Lisbonne')->assertOk()->assertJsonPath('places.0.country_code', 'PT');
        } finally {
            unlink($file);
        }
    }

    public function test_world_ports_keep_existing_osm_places_and_do_not_invent_depths(): void
    {
        Port::create(['slug' => 'existing', 'name' => 'Existing OSM port', 'latitude' => 43, 'longitude' => 5, 'source' => 'osm', 'source_ref' => 'node/1']);
        $file = tempnam(sys_get_temp_dir(), 'mariwaze-ports-');
        file_put_contents($file, json_encode(['ports' => [
            ['portNumber' => 123, 'portName' => 'Suva Harbor', 'countryCode' => 'FJ', 'regionName' => 'Fiji', 'ycoord' => -18.13, 'xcoord' => 178.42, 'chDepth' => '9', 'suWater' => 'Y'],
            ['portNumber' => 124, 'portName' => 'Invalid', 'countryCode' => 'AU', 'ycoord' => 120, 'xcoord' => 170],
        ]], JSON_THROW_ON_ERROR));
        try {
            $importer = app(WorldCatalogueImporter::class);
            $this->assertSame(1, $importer->ports($file));
            $id = Port::where('source', 'nga-wpi')->value('id');
            $importer->ports($file);
            $this->assertDatabaseCount('ports', 2);
            $port = Port::findOrFail($id);
            $this->assertSame('FJ', $port->country_code);
            $this->assertTrue($port->has_water);
            $this->assertNull($port->min_depth_meters);
            $this->get('/explorer?country=FJ')->assertInertia(fn (Assert $page) => $page->has('spots', 1)->where('spots.0.country_code', 'FJ'));
        } finally {
            unlink($file);
        }
    }

    public function test_proximity_and_seamarks_work_on_both_sides_of_the_antimeridian(): void
    {
        foreach ([179.8, -179.8, -170] as $index => $longitude) {
            Port::create(['slug' => 'pacific-'.$index, 'name' => 'Pacific '.$index, 'latitude' => -18, 'longitude' => $longitude, 'country_code' => 'FJ']);
            Seamark::create(['source_ref' => 'node/'.$index, 'name' => 'Pacific mark '.$index, 'latitude' => -18, 'longitude' => $longitude, 'seamark_type' => 'light_major', 'category' => 'lighthouse']);
        }
        $this->assertCount(2, app(StopFinder::class)->portsNear(-18, 179.95, 30));
        $this->assertCount(2, app(StopFinder::class)->portsNear(-18, -179.95, 30));
        $this->getJson('/api/seamarks?south=-18.5&north=-17.5&west=179.5&east=-179.5')->assertOk()->assertJsonCount(2);
    }

    public function test_unknown_foreign_emergency_contacts_do_not_fall_back_to_french_numbers(): void
    {
        $this->seed(MaritimeSafetySeeder::class);
        Port::create(['slug' => 'suva', 'name' => 'Suva', 'latitude' => -18.13, 'longitude' => 178.42, 'country_code' => 'FJ']);
        $this->assertSame([], app(SafetyAuditor::class)->nearestEmergencyContacts(-18.13, 178.42));
    }

    public function test_country_filter_applies_to_ports_and_anchorages_worldwide(): void
    {
        Anchorage::create(['slug' => 'pacific', 'name' => 'Pacific anchorage', 'latitude' => -18, 'longitude' => 178, 'country_code' => 'FJ']);
        Anchorage::create(['slug' => 'france', 'name' => 'French anchorage', 'latitude' => 43, 'longitude' => 5, 'country_code' => 'FR']);
        $this->get('/explorer?country=FJ')->assertInertia(fn (Assert $page) => $page
            ->has('spots', 1)->where('spots.0.slug', 'pacific')->where('filters.country', 'FJ'));
        $this->getJson('/explorer?country=FRA')->assertUnprocessable();
    }
}
