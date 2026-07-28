<?php

namespace Tests\Feature;

use App\Models\WaterwayEdge;
use App\Models\WaterwayNode;
use App\Services\Navigation\InlandRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlandRouteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_a_route_over_the_imported_waterway_network(): void
    {
        $origin = WaterwayNode::create(['latitude' => 48.63293, 'longitude' => -2.06274]);
        $middle = WaterwayNode::create(['latitude' => 48.55000, 'longitude' => -2.05600]);
        $destination = WaterwayNode::create(['latitude' => 48.45553, 'longitude' => -2.05049]);

        WaterwayEdge::create(['from_node_id' => $origin->id, 'to_node_id' => $middle->id, 'distance_meters' => 9400, 'lock_count' => 1]);
        WaterwayEdge::create(['from_node_id' => $middle->id, 'to_node_id' => $destination->id, 'distance_meters' => 10500, 'lock_count' => 1]);

        $route = app(InlandRouteService::class)->route(
            ['latitude' => 48.63293, 'longitude' => -2.06274],
            ['latitude' => 48.45553, 'longitude' => -2.05049],
        );

        $this->assertNotNull($route);
        $this->assertSame(11, $route['miles']);
        $this->assertSame(2, $route['locks']);
        $this->assertCount(5, $route['geometry']);
    }
}
