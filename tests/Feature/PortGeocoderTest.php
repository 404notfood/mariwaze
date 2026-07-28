<?php

namespace Tests\Feature;

use App\Services\Navigation\PortCoordinates;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortGeocoderTest extends TestCase
{
    public function test_an_unknown_port_can_be_resolved_by_the_configured_geocoding_api(): void
    {
        config()->set('services.geocoding.mapbox_token', 'test-token');
        Cache::forget('port-geocoder:'.sha1('port test'));

        Http::fake([
            'api.mapbox.com/*' => Http::response([
                'features' => [[
                    'name' => 'Port Test',
                    'geometry' => ['coordinates' => [3.12, 43.31]],
                ]],
            ]),
        ]);

        $location = app(PortCoordinates::class)->find('Port Test');

        $this->assertSame([
            'name' => 'Port Test',
            'longitude' => 3.12,
            'latitude' => 43.31,
        ], $location);
        Http::assertSentCount(1);
    }
}
