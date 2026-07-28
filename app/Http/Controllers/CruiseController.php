<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use App\Services\Accounts\AccountContext;
use App\Services\Checklists\CruiseChecklistBuilder;
use App\Services\Itinerary\CruiseDurationEstimator;
use App\Services\Itinerary\DraftItineraryPlanner;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\SeaRouteService;
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
        private readonly DraftItineraryPlanner $planner,
        private readonly PortCoordinates $coordinates,
        private readonly SeaRouteService $router,
        private readonly AccountContext $accounts,
        private readonly CruiseChecklistBuilder $checklists,
    ) {}

    public function index(Request $request): Response
    {
        $organization = $this->accounts->currentOrDemo($request->user());

        return Inertia::render('demo/cruises', [
            'cruises' => Cruise::query()->where('organization_id', $organization->id)->latest()->get()->map(fn (Cruise $cruise) => [
                ...$this->summary($this->fromModel($cruise)),
                'deletable' => true,
            ])->all(),
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
            return $data['duration_days'];
        }

        $cruise = new Cruise($data);
        $recommendedDays = $this->planner->plan($cruise)['scenarios'][0]['days'] ?? null;

        return is_int($recommendedDays)
            ? $recommendedDays
            : app(CruiseDurationEstimator::class)->estimate($data['departure_port'], $data['destination_port'], $data['navigation_style']);
    }

    public function show(string $slug): Response
    {
        $model = Cruise::query()->where('slug', $slug)->first();

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        $cruise = $this->fromModel($model);

        return Inertia::render('demo/cruise-show', ['cruise' => $cruise]);
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
        $plan = $this->planner->plan($cruise);
        $legs = is_array($plan['legs'] ?? null) ? $plan['legs'] : [];
        $routeGeometry = $plan['route_geometry'] ?? $this->routeGeometry([
            $cruise->departure_port,
            ...collect($legs)->pluck('to')->all(),
        ]);
        $departureDate = $this->departureDate($cruise);

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
            'experience' => 'À préciser',
            'progress' => 8,
            ...$plan,
            'route_available' => ($plan['route_available'] ?? true) && count($routeGeometry) > 1,
            'route_geometry' => $routeGeometry,
        ];
    }

    private function departureDate(Cruise $cruise): Carbon
    {
        return Carbon::parse($cruise->departure_date);
    }

    /** @param array<int, string> $names
     * @return array<int, array{latitude: float, longitude: float}>
     */
    private function routeGeometry(array $names): array
    {
        $points = collect($names)->map(fn (string $name) => $this->coordinates->find($name))->filter()->values()->all();

        if (count($points) < 2) {
            return [];
        }

        $geometry = [];
        foreach (array_chunk($points, 2, true) as $segment) {
            $segment = array_values($segment);
            if (count($segment) !== 2) {
                continue;
            }

            try {
                $line = $this->router->route($segment[0], $segment[1]);
                if (count($line) < 2) {
                    return [];
                }
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
}
