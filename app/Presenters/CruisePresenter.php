<?php

namespace App\Presenters;

use App\Models\Boat;
use App\Models\Cruise;
use App\Models\CruiseRoute;
use App\Models\RouteSegment;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Routing\RouteScenario;
use App\Services\Safety\RouteHazardService;
use Illuminate\Support\Carbon;

class CruisePresenter
{
    public function __construct(
        private readonly CruiseDurationEstimator $durations,
        private readonly RouteHazardService $hazards,
    ) {}

    /** @return array<string, mixed> */
    public function summary(Cruise $cruise): array
    {
        return array_intersect_key($this->detail($cruise), array_flip([
            'slug', 'title', 'route', 'departure_date', 'duration', 'status', 'progress', 'miles', 'stops', 'style', 'route_available',
        ]));
    }

    /** @return array<string, mixed> */
    public function detail(Cruise $cruise): array
    {
        $departureDate = $this->departureDate($cruise);
        $selected = $cruise->routes->firstWhere('is_selected', true) ?? $cruise->routes->first();
        $distance = $selected instanceof CruiseRoute ? $selected->distance_nm : 0;
        $stops = $selected instanceof CruiseRoute ? $selected->stops : 0;
        $geometry = $selected instanceof CruiseRoute ? ($selected->geometry ?? []) : [];
        $recommendedDays = $this->durations->estimate(
            $cruise->departure_port,
            $cruise->destination_port,
            $cruise->navigation_style,
            $cruise->departure_place,
            $cruise->destination_place,
        );

        return [
            'slug' => $cruise->slug,
            'title' => $cruise->title,
            'route' => $cruise->departure_port.' → '.$cruise->destination_port,
            'departure_port' => $cruise->departure_port,
            'arrival_port' => $cruise->destination_port,
            'departure_date' => $departureDate->translatedFormat('d F Y'),
            'return_date' => $departureDate->copy()->addDays($cruise->duration_days)->translatedFormat('d F Y'),
            'duration' => $cruise->duration_days.' jours',
            'status' => $cruise->status,
            'style' => $cruise->navigation_style,
            'crew' => $cruise->crew_count,
            'navigation_area' => $cruise->navigation_area,
            'boat' => $cruise->boat?->only(['id', 'name', 'model', 'boat_type', 'avg_speed', 'fuel_range']),
            'miles' => $distance,
            'stops' => $stops,
            'score' => $selected?->score,
            'route_available' => count($geometry) > 1,
            'route_geometry' => $geometry,
            'route_generation_status' => $cruise->route_generation_status,
            'route_generation_error' => $cruise->route_generation_error,
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "Le style {$cruise->navigation_style} demande au moins {$recommendedDays} jours pour cette distance."
                : null,
        ];
    }

    /** @return array<string, mixed> */
    public function scenario(CruiseRoute $route, Cruise $cruise): array
    {
        $boat = $cruise->boat;
        $safeDepth = $boat instanceof Boat ? $boat->draft + 0.5 : null;

        return [
            'scenario' => $route->scenario,
            'description' => RouteScenario::tryFrom($route->scenario)?->description(),
            'daily_target_hours' => RouteScenario::tryFrom($route->scenario)?->maxHoursPerDay(),
            'max_leg_hours' => $route->segments->max('duration_hours'),
            'boat_context' => $boat === null
                ? 'Aucun bateau associé : compatibilité à confirmer, vitesse de calcul 6 nœuds.'
                : sprintf('%s : tirant d’eau %.1f m, marge de calcul 0,5 m, vitesse %.1f nœuds.', $boat->name, $boat->draft, $boat->avg_speed),
            'weather_complete_legs' => $route->segments->filter(fn (RouteSegment $segment): bool => ($segment->weather_snapshot['available'] ?? false) === true)->count(),
            'label' => $route->label,
            'selected' => $route->is_selected,
            'score' => $route->score,
            'score_breakdown' => $route->score_breakdown,
            'distance_nm' => $route->distance_nm,
            'duration_days' => $route->duration_days,
            'stops' => $route->stops,
            'geometry' => $route->geometry ?? [],
            'computed_at' => $route->computed_at?->toIso8601String(),
            'hazards' => $this->hazards->alertsForGeometry(
                $route->geometry ?? [],
                $this->departureDate($cruise),
                $cruise->duration_days,
            ),
            'legs' => $route->segments->map(fn (RouteSegment $segment): array => [
                'day' => $segment->day,
                'label' => 'J'.$segment->day,
                'from' => $segment->from_name,
                'to' => $segment->to_name,
                'latitude' => $segment->to_latitude,
                'longitude' => $segment->to_longitude,
                'kind' => $segment->anchorage !== null ? 'mouillage' : ($segment->port !== null ? 'port' : 'point'),
                'distance_nm' => $segment->distance_nm,
                'duration_hours' => $segment->duration_hours,
                'min_depth_meters' => $segment->min_depth_meters,
                'depth_warning' => $segment->min_depth_meters !== null
                    && $safeDepth !== null
                    && $segment->min_depth_meters < $safeDepth,
                'overnight' => $segment->overnight,
                'weather' => $segment->weather_snapshot,
                'note' => $segment->note,
                'anchorage_slug' => $segment->anchorage?->slug,
                'port_slug' => $segment->port?->slug,
            ])->all(),
        ];
    }

    private function departureDate(Cruise $cruise): Carbon
    {
        return Carbon::parse($cruise->departure_date);
    }
}
