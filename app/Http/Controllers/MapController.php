<?php

namespace App\Http\Controllers;

use App\Exceptions\MarineConditionsUnavailable;
use App\Models\Anchorage;
use App\Models\Port;
use App\Services\Accounts\CurrentContext;
use App\Services\Marine\Contracts\WeatherProviderInterface;
use App\Services\Routing\StopFinder;
use App\Services\Safety\RouteHazardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function __construct(
        private readonly WeatherProviderInterface $weather,
        private readonly StopFinder $stops,
        private readonly CurrentContext $context,
        private readonly RouteHazardService $hazards,
    ) {}

    public function index(Request $request): Response
    {
        $cruise = $this->context->cruise($request->user());
        $route = null;
        $conditions = null;

        if ($cruise?->selectedRoute !== null) {
            $selected = $cruise->selectedRoute;

            $points = [[
                'name' => $cruise->departure_port,
                'latitude' => $selected->segments->first()?->from_latitude,
                'longitude' => $selected->segments->first()?->from_longitude,
                'kind' => 'port',
            ]];

            foreach ($selected->segments as $segment) {
                $points[] = [
                    'name' => $segment->to_name,
                    'latitude' => $segment->to_latitude,
                    'longitude' => $segment->to_longitude,
                    'kind' => $segment->anchorage_id !== null ? 'mouillage' : 'port',
                ];
            }

            $points = array_values(array_filter(
                $points,
                fn (array $point): bool => $point['latitude'] !== null && $point['longitude'] !== null,
            ));

            $route = [
                'slug' => $cruise->slug,
                'title' => $cruise->title,
                'scenario' => $selected->scenario,
                'label' => $selected->label,
                'score' => $selected->score,
                'points' => $points,
                'geometry' => $selected->geometry ?? [],
                'available' => count($selected->geometry ?? []) > 1,
                'hazards' => $this->hazards->alertsForGeometry(
                    $selected->geometry ?? [],
                    $cruise->departure_date,
                    (int) $cruise->duration_days,
                ),
            ];

            if ($points !== []) {
                try {
                    $conditions = $this->weather->conditions($points[0]['latitude'], $points[0]['longitude']);
                } catch (MarineConditionsUnavailable) {
                    $conditions = null;
                }
            }
        }

        return Inertia::render('map', [
            'route' => $route,
            'conditions' => $conditions,
            'nearby' => $this->nearby($route),
        ]);
    }

    /**
     * Escales réelles autour du départ, affichées sur la carte.
     *
     * @param  array<string, mixed>|null  $route
     * @return array<string, mixed>
     */
    private function nearby(?array $route): array
    {
        $origin = $route['points'][0] ?? null;

        if ($origin === null) {
            return ['ports' => [], 'anchorages' => []];
        }

        return [
            'ports' => $this->stops->portsNear($origin['latitude'], $origin['longitude'], 30, 20)
                ->map(fn (Port $port): array => [
                    'slug' => $port->slug,
                    'name' => $port->name,
                    'latitude' => $port->latitude,
                    'longitude' => $port->longitude,
                ])->values()->all(),
            'anchorages' => $this->stops->anchoragesNear($origin['latitude'], $origin['longitude'], 30, 20)
                ->map(fn (Anchorage $anchorage): array => [
                    'slug' => $anchorage->slug,
                    'name' => $anchorage->name,
                    'latitude' => $anchorage->latitude,
                    'longitude' => $anchorage->longitude,
                ])->values()->all(),
        ];
    }
}
