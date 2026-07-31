<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Cruise;
use App\Models\CruiseRoute;
use App\Models\RouteSegment;
use App\Models\WaterwayNode;
use App\Services\Accounts\AccountContext;
use App\Services\Checklists\CruiseChecklistBuilder;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Routing\RouteGenerator;
use App\Services\Safety\RouteHazardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CruiseController extends Controller
{
    public function __construct(
        private readonly AccountContext $accounts,
        private readonly CruiseChecklistBuilder $checklists,
        private readonly RouteGenerator $routes,
        private readonly CruiseDurationEstimator $durations,
        private readonly RouteHazardService $hazards,
    ) {}

    public function index(Request $request): Response
    {
        $organization = $this->accounts->currentOrDemo($request->user());

        return Inertia::render('cruises/index', [
            'cruises' => Cruise::query()
                ->with('routes')
                ->where('organization_id', $organization->id)
                ->latest()
                ->get()
                ->map(fn (Cruise $cruise): array => [
                    ...$this->summary($this->fromModel($cruise)),
                    'deletable' => true,
                ])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('cruises/create', [
            'prefill' => [
                'departure_port' => $request->string('departure')->toString(),
                'destination_port' => $request->string('destination')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCruise($request);
        $organization = $this->accounts->currentOrDemo($request->user());

        $cruise = Cruise::create([
            ...$data,
            'duration_days' => $this->duration($data),
            'user_id' => $request->user()?->id,
            'organization_id' => $organization->id,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
        ]);
        $this->checklists->build($cruise->load('boat'));

        return to_route('cruise', $cruise->slug);
    }

    public function edit(string $slug): Response
    {
        $cruise = Cruise::query()->where('slug', $slug)->firstOrFail();

        return Inertia::render('cruises/create', [
            'cruise' => [
                ...$cruise->only(['slug', 'title', 'departure_port', 'destination_port', 'duration_days', 'crew_count', 'navigation_style', 'navigation_area', 'boat_id']),
                'departure_date' => $this->departureDate($cruise)->toDateString(),
            ],
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $cruise = Cruise::query()->where('slug', $slug)->firstOrFail();
        $data = $this->validatedCruise($request);

        $cruise->update([
            ...$data,
            'duration_days' => $this->duration($data),
        ]);

        return to_route('cruise', $cruise->slug);
    }

    /** @return array<string, mixed> */
    private function validatedCruise(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'departure_port' => ['required', 'string', 'max:120'],
            'destination_port' => ['required', 'string', 'max:120'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'duration_days' => ['nullable', 'required_unless:auto_duration,true', 'integer', 'between:1,180'],
            'auto_duration' => ['required', 'boolean'],
            'crew_count' => ['required', 'integer', 'between:1,20'],
            'navigation_area' => ['nullable', 'in:coastal,offshore,inland'],
            'boat_id' => ['nullable', 'exists:boats,id'],
            'navigation_style' => ['required', 'in:Rapide,Confort,Découverte,Famille'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function duration(array $data): int
    {
        if (! $data['auto_duration']) {
            return (int) $data['duration_days'];
        }

        return $this->durations->estimate(
            $data['departure_port'],
            $data['destination_port'],
            $data['navigation_style'],
        );
    }

    public function show(string $slug): Response
    {
        $model = Cruise::query()
            ->with(['boat', 'routes.segments.port', 'routes.segments.anchorage'])
            ->where('slug', $slug)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('cruises/show', [
            'cruise' => $this->fromModel($model),
            'scenarios' => $model->routes->map(fn (CruiseRoute $route): array => $this->routePayload($route, $model))->all(),
        ]);
    }

    /**
     * Calcule (ou recalcule) les routes candidates de la croisière.
     */
    public function generateRoutes(string $slug): RedirectResponse
    {
        $cruise = Cruise::query()->with('boat')->where('slug', $slug)->firstOrFail();

        $routes = $this->routes->generate($cruise);

        if ($routes === []) {
            if ($cruise->navigation_area === 'inland') {
                $message = WaterwayNode::query()->exists()
                    ? "L'itinéraire fluvial n'a pas pu être calculé : aucun chemin navigable ne relie ces deux ports dans le réseau importé."
                    : "L'itinéraire fluvial n'a pas pu être calculé : le réseau de voies navigables n'est pas encore importé (waterways:import-overpass).";
            } else {
                $message = "L'itinéraire n'a pas pu être calculé : vérifiez que les deux ports sont localisables par mer.";
            }

            return back()->with('error', $message);
        }

        return back()->with('success', count($routes).' itinéraires calculés.');
    }

    /**
     * Retient l'un des scénarios proposés.
     */
    public function selectRoute(string $slug, string $scenario): RedirectResponse
    {
        $cruise = Cruise::query()->where('slug', $slug)->firstOrFail();

        $route = $cruise->routes()->where('scenario', $scenario)->firstOrFail();

        $cruise->routes()->update(['is_selected' => false]);
        $route->update(['is_selected' => true]);

        return back()->with('success', 'Itinéraire « '.$route->label.' » retenu.');
    }

    /** @return array<string, mixed> */
    private function routePayload(CruiseRoute $route, Cruise $cruise): array
    {
        // Marge de pilote : tirant d'eau + 50 cm sous la quille.
        $boat = $cruise->boat;
        $safeDepth = $boat instanceof Boat ? $boat->draft + 0.5 : null;

        return [
            'scenario' => $route->scenario,
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

    public function destroy(string $slug): RedirectResponse
    {
        $cruise = Cruise::query()->where('slug', $slug)->firstOrFail();

        $cruise->delete();

        return to_route('cruises');
    }

    /**
     * @param  array<string, mixed>  $cruise
     * @return array<string, mixed>
     */
    private function summary(array $cruise): array
    {
        return array_intersect_key($cruise, array_flip([
            'slug', 'title', 'route', 'departure_date', 'duration', 'status', 'progress', 'miles', 'stops', 'style', 'route_available',
        ]));
    }

    /** @return array<string, mixed> */
    private function fromModel(Cruise $cruise): array
    {
        $departureDate = $this->departureDate($cruise);
        $selected = $cruise->routes->firstWhere('is_selected', true) ?? $cruise->routes->first();
        $recommendedDays = $this->durations->estimate(
            $cruise->departure_port,
            $cruise->destination_port,
            $cruise->navigation_style,
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
            'miles' => $selected?->distance_nm ?? 0,
            'stops' => $selected?->stops ?? 0,
            'score' => $selected?->score,
            'route_available' => $selected !== null && count($selected->geometry ?? []) > 1,
            'route_geometry' => $selected?->geometry ?? [],
            'duration_warning' => $cruise->duration_days < $recommendedDays
                ? "Le style {$cruise->navigation_style} demande au moins {$recommendedDays} jours pour cette distance."
                : null,
        ];
    }

    private function departureDate(Cruise $cruise): Carbon
    {
        return Carbon::parse($cruise->departure_date);
    }
}
