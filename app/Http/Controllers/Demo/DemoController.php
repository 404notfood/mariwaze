<?php

namespace App\Http\Controllers\Demo;

use App\Data\Mock\AnchorageMockDataProvider;
use App\Data\Mock\BoatMockDataProvider;
use App\Data\Mock\CruiseMockDataProvider;
use App\Data\Mock\DashboardMockDataProvider;
use App\Data\Mock\SecurityMockDataProvider;
use App\Exceptions\MarineConditionsUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Cruise;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Itinerary\DraftItineraryPlanner;
use App\Services\Marine\OpenMeteoMarineService;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\SeaRouteService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DemoController extends Controller
{
    public function __construct(
        private readonly DashboardMockDataProvider $dashboardMock,
        private readonly CruiseMockDataProvider $cruiseMock,
        private readonly AnchorageMockDataProvider $anchorageMock,
        private readonly BoatMockDataProvider $boatMock,
        private readonly SecurityMockDataProvider $securityMock,
    ) {}

    private function isPreviewMode(): bool
    {
        return request()->routeIs('demo.*');
    }

    public function dashboard(): Response
    {
        $cruise = Cruise::query()->latest()->first();
        $boat = Boat::query()->latest()->first();
        $cruiseData = null;

        if ($cruise !== null) {
            $plan = app(DraftItineraryPlanner::class)->plan($cruise);
            $coordinates = app(PortCoordinates::class);
            $points = collect([$cruise->departure_port, ...collect($plan['legs'])->pluck('to')->all()])
                ->map(fn (string $name): ?array => $coordinates->find($name))
                ->filter()
                ->values()
                ->all();
            $recommendedDays = app(CruiseDurationEstimator::class)->estimate(
                $cruise->departure_port,
                $cruise->destination_port,
                $cruise->navigation_style,
            );

            $routeGeometry = $plan['route_geometry'] ?? $this->seaRouteGeometry($points);
            $cruiseData = [
                'slug' => $cruise->slug,
                'title' => $cruise->title,
                'departure_port' => $cruise->departure_port,
                'destination_port' => $cruise->destination_port,
                'departure_date' => $cruise->departure_date->translatedFormat('d F Y'),
                'duration_days' => $cruise->duration_days,
                'recommended_duration_days' => $recommendedDays,
                'duration_warning' => $cruise->duration_days < $recommendedDays,
                'route_geometry' => $routeGeometry,
                'route_available' => ($plan['route_available'] ?? true) && count($routeGeometry) > 1,
                'crew_count' => $cruise->crew_count,
                'style' => $cruise->navigation_style,
                'status' => $cruise->status,
            ];
        }

        return Inertia::render('dashboard', [
            'dashboardData' => [
                'boat' => $boat === null ? null : [
                    'name' => $boat->name,
                    'model' => $boat->model,
                    'type' => $boat->boat_type,
                    'draft' => $boat->draft,
                    'mast_height' => $boat->mast_height,
                ],
                'cruise' => $cruiseData,
            ],
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function cruises(): Response
    {
        return Inertia::render('demo/cruises', [
            'cruises' => [
                $this->cruiseMock->find('tour-corse-2027'),
            ],
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function cruise(string $slug): Response
    {
        if ($slug !== 'tour-corse-2027') {
            throw new NotFoundHttpException;
        }

        return Inertia::render('demo/cruise-show', [
            'cruise' => $this->cruiseMock->find($slug),
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function anchorage(string $slug): Response
    {
        $anchorage = $this->anchorageMock->find($slug);

        if ($anchorage === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('demo/anchorage-show', [
            'anchorage' => $anchorage,
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function explore(): Response
    {
        return Inertia::render('explore', [
            'locations' => app(PortCoordinates::class)->all(),
        ]);
    }

    public function map(): Response
    {
        $cruise = Cruise::query()->latest()->first();
        $route = null;

        if ($cruise !== null) {
            $plan = app(DraftItineraryPlanner::class)->plan($cruise);
            $names = [$cruise->departure_port, ...collect($plan['legs'])->pluck('to')->all()];
            $coordinates = app(PortCoordinates::class);
            $route = [
                'slug' => $cruise->slug,
                'title' => $cruise->title,
                'points' => collect($names)->map(function (string $name, int $index) use ($coordinates, $plan): ?array {
                    $position = $coordinates->find($name);

                    if ($position === null) {
                        return null;
                    }

                    return [
                        'name' => $name,
                        'latitude' => $position['latitude'],
                        'longitude' => $position['longitude'],
                        'kind' => $index > 0 && ($plan['legs'][$index - 1]['overnight'] ?? '') === 'Mouillage' ? 'mouillage' : 'port',
                    ];
                })->filter()->values()->all(),
            ];
            $route['geometry'] = $plan['route_geometry'] ?? $this->seaRouteGeometry($route['points']);
            $route['available'] = ($plan['route_available'] ?? true) && count($route['geometry']) > 1;
        }

        $conditions = null;
        if (($route['points'][0] ?? null) !== null) {
            try {
                $conditions = app(OpenMeteoMarineService::class)->conditions(
                    $route['points'][0]['latitude'],
                    $route['points'][0]['longitude'],
                );
            } catch (MarineConditionsUnavailable) {
                $conditions = null;
            }
        }

        return Inertia::render('demo/map', [
            'route' => $route,
            'conditions' => $conditions,
            'preview' => $this->isPreviewMode(),
        ]);
    }

    /** @param array<int, array{latitude: float, longitude: float}> $points
     * @return array<int, array{latitude: float, longitude: float}>
     */
    private function seaRouteGeometry(array $points): array
    {
        if (count($points) < 2) {
            return $points;
        }

        $geometry = [];
        $router = app(SeaRouteService::class);

        foreach (array_chunk($points, 2, true) as $segment) {
            $segment = array_values($segment);
            if (count($segment) < 2) {
                continue;
            }

            try {
                $line = $router->route($segment[0], $segment[1]);
                if ($geometry !== []) {
                    array_shift($line);
                }
                array_push($geometry, ...$line);
            } catch (\RuntimeException) {
                return [];
            }
        }

        return $geometry;
    }

    public function boat(): Response
    {
        return Inertia::render('demo/boat-show', [
            'boat' => $this->boatMock->toArray(),
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function boatSetup(): Response
    {
        return Inertia::render('demo/boat-setup', [
            'boat' => $this->boatMock->toArray(),
            'preview' => $this->isPreviewMode(),
        ]);
    }

    public function security(): Response
    {
        return Inertia::render('demo/security', [
            'securityData' => $this->securityMock->toArray(),
            'preview' => $this->isPreviewMode(),
        ]);
    }
}
