<?php

namespace App\Http\Controllers;

use App\Http\Requests\CruiseRequest;
use App\Jobs\GenerateCruiseRoutes;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\CruiseChecklist;
use App\Models\CruiseRoute;
use App\Presenters\CruisePresenter;
use App\Services\Accounts\AccountContext;
use App\Services\Checklists\CruiseChecklistBuilder;
use App\Services\Itinerary\CruiseDurationEstimator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CruiseController extends Controller
{
    public function __construct(
        private readonly AccountContext $accounts,
        private readonly CruiseChecklistBuilder $checklists,
        private readonly CruiseDurationEstimator $durations,
        private readonly CruisePresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $organization = $this->accounts->currentOrCreate($request->user());

        return Inertia::render('cruises/index', [
            'cruises' => Cruise::query()
                ->with('routes')
                ->where('organization_id', $organization->id)
                ->latest()
                ->get()
                ->map(fn (Cruise $cruise): array => [
                    ...$this->presenter->summary($cruise),
                    'deletable' => true,
                ])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('cruises/create', [
            'boats' => $this->boats($request),
            'prefill' => [
                'departure_port' => $request->string('departure')->toString(),
                'destination_port' => $request->string('destination')->toString(),
                'departure_place' => $request->input('departure_place'),
                'destination_place' => $request->input('destination_place'),
            ],
        ]);
    }

    public function store(CruiseRequest $request): RedirectResponse
    {
        $organization = $this->accounts->currentOrCreate($request->user());
        $this->authorize('create', Cruise::class);
        $data = $request->validated();

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

    public function edit(Request $request, string $slug): Response
    {
        $cruise = $this->cruiseForAccount($request, $slug);
        $this->authorize('update', $cruise);

        return Inertia::render('cruises/create', [
            'boats' => $this->boats($request),
            'cruise' => [
                ...$cruise->only(['departure_place', 'destination_place']),
                ...$cruise->only(['slug', 'title', 'departure_port', 'destination_port', 'duration_days', 'crew_count', 'navigation_style', 'navigation_area', 'boat_id']),
                'departure_date' => $this->departureDate($cruise)->toDateString(),
            ],
        ]);
    }

    public function update(CruiseRequest $request, string $slug): RedirectResponse
    {
        $cruise = $this->cruiseForAccount($request, $slug);
        $this->authorize('update', $cruise);
        $data = $request->validated();

        $duration = $this->duration($data);
        DB::transaction(function () use ($cruise, $data, $duration): void {
            $current = Cruise::whereKey($cruise->id)->lockForUpdate()->firstOrFail();
            if (in_array($current->route_generation_status, ['pending', 'running'], true)) {
                throw ValidationException::withMessages(['title' => 'Attendez la fin du calcul avant de modifier cette croisière.']);
            }
            $current->fill([...$data, 'duration_days' => $duration]);
            if ($current->isDirty(['departure_port', 'destination_port', 'departure_place', 'destination_place', 'departure_date', 'duration_days', 'navigation_style', 'navigation_area', 'boat_id'])) {
                $current->routes()->delete();
                $current->fill(['route_generation_status' => 'idle', 'route_generation_token' => null, 'route_generation_error' => null, 'route_generation_started_at' => null, 'route_generation_completed_at' => null]);
            }
            $refreshChecklist = $current->isDirty(['crew_count', 'duration_days', 'boat_id']);
            $current->save();
            if ($refreshChecklist) {
                $this->checklists->refreshQuantities($current->load('boat'));
            }
        });

        return to_route('cruise', $cruise->slug);
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
            $data['departure_place'] ?? null,
            $data['destination_place'] ?? null,
        );
    }

    public function show(Request $request, string $slug): Response
    {
        $model = $this->cruiseForAccount($request, $slug, ['boat', 'routes.segments.port', 'routes.segments.anchorage']);
        $this->authorize('view', $model);

        return Inertia::render('cruises/show', [
            'cruise' => $this->presenter->detail($model),
            'checklists' => CruiseChecklist::with('items')->where('cruise_id', $model->id)->get()->map(fn ($checklist): array => [
                'name' => $checklist->name,
                'items' => $checklist->items->map(fn ($item): array => $item->only(['id', 'section', 'label', 'quantity', 'required', 'checked']))->all(),
            ])->all(),
            'scenarios' => $model->routes->map(fn (CruiseRoute $route): array => $this->presenter->scenario($route, $model))->all(),
        ]);
    }

    /**
     * Calcule (ou recalcule) les routes candidates de la croisière.
     */
    public function generateRoutes(Request $request, string $slug): RedirectResponse
    {
        $cruise = $this->cruiseForAccount($request, $slug, ['boat']);
        $this->authorize('update', $cruise);

        $generationActivity = $cruise->route_generation_started_at ?? $cruise->updated_at;

        if (in_array($cruise->route_generation_status, ['pending', 'running'], true)
            && Carbon::parse($generationActivity)->isAfter(now()->subMinutes(15))) {
            return back()->with('error', 'Un calcul est déjà en cours pour cette croisière.');
        }

        $token = (string) Str::uuid();
        $cruise->update([
            'route_generation_status' => 'pending',
            'route_generation_token' => $token,
            'route_generation_error' => null,
            'route_generation_started_at' => null,
            'route_generation_completed_at' => null,
        ]);
        GenerateCruiseRoutes::dispatch($cruise->id, $token)->afterCommit();

        return back()->with('success', 'Le calcul des itinéraires a été lancé.');
    }

    /**
     * Retient l'un des scénarios proposés.
     */
    public function selectRoute(Request $request, string $slug, string $scenario): RedirectResponse
    {
        $cruise = $this->cruiseForAccount($request, $slug);
        $this->authorize('update', $cruise);

        $route = DB::transaction(function () use ($cruise, $scenario): CruiseRoute {
            Cruise::whereKey($cruise->id)->lockForUpdate()->firstOrFail();
            $route = $cruise->routes()->where('scenario', $scenario)->firstOrFail();
            $cruise->routes()->update(['is_selected' => false]);
            $route->update(['is_selected' => true]);

            return $route;
        });

        return back()->with('success', 'Itinéraire « '.$route->label.' » retenu.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $cruise = $this->cruiseForAccount($request, $slug);
        $this->authorize('delete', $cruise);

        $cruise->delete();

        return to_route('cruises');
    }

    private function departureDate(Cruise $cruise): Carbon
    {
        return Carbon::parse($cruise->departure_date);
    }

    /** @return list<array<string, mixed>> */
    private function boats(Request $request): array
    {
        $organization = $this->accounts->currentOrCreate($request->user());

        return array_values(Boat::where('organization_id', $organization->id)->orderBy('name')->get(['id', 'name', 'draft'])
            ->map(fn (Boat $boat): array => $boat->only(['id', 'name', 'draft']))->all());
    }

    /**
     * @param  list<string>  $relations
     */
    private function cruiseForAccount(Request $request, string $slug, array $relations = []): Cruise
    {
        $organization = $this->accounts->currentOrCreate($request->user());

        return Cruise::query()
            ->with($relations)
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
