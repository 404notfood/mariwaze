<?php

namespace Tests\Unit;

use App\Services\Routing\RouteGeometry;
use PHPUnit\Framework\TestCase;

class RouteGeometryTest extends TestCase
{
    public function test_it_splits_a_geometry_and_preserves_the_destination(): void
    {
        $geometry = [
            ['latitude' => 0.0, 'longitude' => 0.0],
            ['latitude' => 0.0, 'longitude' => 0.5],
            ['latitude' => 0.0, 'longitude' => 1.0],
        ];

        $waypoints = (new RouteGeometry)->split($geometry, 20.0);

        $this->assertSame([1, 2], array_column($waypoints, 'index'));
        $this->assertSame($geometry[2], $waypoints[1]['point']);
    }

    public function test_it_measures_and_reconnects_route_legs(): void
    {
        $geometry = [
            ['latitude' => 0.0, 'longitude' => 0.0],
            ['latitude' => 0.0, 'longitude' => 1.0],
        ];
        $service = new RouteGeometry;

        $this->assertEqualsWithDelta(60.0, $service->distance($geometry), 0.1);
        $this->assertSame([$geometry], $service->stitch([[]], $geometry));
    }
}
