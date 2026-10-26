<?php

namespace App\Http\Controllers;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Port;
use App\Services\Accounts\AccountContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:port,anchorage'],
            'min_depth' => ['nullable', 'numeric', 'between:0,100'],
            'service' => ['nullable', 'in:has_fuel,has_water,has_electricity,has_shower,has_repair,has_provisions'],
            'protection' => ['nullable', 'in:N,NE,E,SE,S,SW,W,NW'],
            'boat_id' => ['nullable', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);
        unset($filters['page']);
        $search = trim($filters['q'] ?? '');
        $type = $filters['type'] ?? null;
        $organization = app(AccountContext::class)->currentOrCreate($request->user());
        $boats = Boat::where('organization_id', $organization->id)->orderBy('name')->get();
        $boat = isset($filters['boat_id']) ? $boats->firstWhere('id', (int) $filters['boat_id']) : null;
        abort_if(isset($filters['boat_id']) && $boat === null, 422, 'Choisissez un bateau de votre flotte.');

        $ports = $this->searchPorts($search, $filters, $boat)
            ->when($type === 'anchorage' || ! empty($filters['protection']), fn ($query) => $query->whereRaw('1 = 0'));
        $anchorages = $this->searchAnchorages($search, $filters, $boat)
            ->when($type === 'port' || ! empty($filters['service']), fn ($query) => $query->whereRaw('1 = 0'));

        // Paginate the combined catalogue before loading cards and review aggregates.
        $catalogue = (clone $ports)->select(['id', 'name'])->selectRaw("'port' as type")->toBase()
            ->unionAll((clone $anchorages)->select(['id', 'name'])->selectRaw("'anchorage' as type")->toBase());
        $pagination = DB::query()->fromSub($catalogue, 'places')
            ->orderByRaw('lower(name)')->orderBy('type')->orderBy('id')
            ->paginate(24)->withQueryString();
        $places = collect($pagination->items());
        $portCards = $ports->whereIn('id', $places->where('type', 'port')->pluck('id'))
            ->withCount(['publicReviews as reviews_count'])
            ->withAvg('publicReviews as reviews_avg_rating', 'rating')->get()->keyBy('id');
        $anchorageCards = $anchorages->whereIn('id', $places->where('type', 'anchorage')->pluck('id'))
            ->withCount(['publicReviews as reviews_count'])
            ->withAvg('publicReviews as reviews_avg_rating', 'rating')->get()->keyBy('id');
        $spots = $places->map(function (stdClass $place) use ($portCards, $anchorageCards): ?array {
            $model = $place->type === 'port' ? $portCards->get($place->id) : $anchorageCards->get($place->id);

            return $model === null ? null : $this->spot($model);
        })->filter()->values()->all();

        return Inertia::render('explore', [
            'filters' => [...$filters, 'q' => $search, 'type' => $type],
            'boats' => $boats->map(fn (Boat $boat): array => $boat->only(['id', 'name', 'draft']))->all(),
            'spots' => $spots,
            'pagination' => [
                'total' => $pagination->total(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'previous_url' => $pagination->previousPageUrl(),
                'next_url' => $pagination->nextPageUrl(),
            ],
            'counts' => [
                'ports' => Port::count(),
                'anchorages' => Anchorage::count(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Port>
     */
    private function searchPorts(string $search, array $filters, ?Boat $boat): Builder
    {
        return $this->filter(Port::query(), $filters, $boat)
            ->when(! empty($filters['service']), fn ($query) => $query->where($filters['service'], true))
            ->when($boat !== null, fn ($query) => $query
                ->where(fn ($q) => $q->whereNull('max_length_meters')->orWhere('max_length_meters', '>=', $boat->length))
                ->where(fn ($q) => $q->whereNull('max_width_meters')->orWhere('max_width_meters', '>=', $boat->width)))
            ->when($search !== '', fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Anchorage>
     */
    private function searchAnchorages(string $search, array $filters, ?Boat $boat): Builder
    {
        return $this->filter(Anchorage::query(), $filters, $boat)
            ->when(! empty($filters['protection']), fn ($query) => $query->whereJsonContains('protected_from', $filters['protection']))
            ->when($search !== '', fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']));
    }

    /** @return array<string, mixed> */
    private function spot(Port|Anchorage $place): array
    {
        return [
            'slug' => $place->slug,
            'name' => $place->name,
            'type' => $place instanceof Port ? 'port' : 'anchorage',
            'region' => $place->region,
            'country_code' => $place->country_code,
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
            'depth' => $place instanceof Port ? $place->min_depth_meters : $place->depthLabel(),
            'rating' => $place->reviews_avg_rating === null ? null : round((float) $place->reviews_avg_rating, 1),
            'reviews_count' => $place->reviews_count,
            ...($place instanceof Port ? ['services' => $place->services()] : [
                'tags' => $place->tags ?? [],
                'protected_from' => $place->protected_from ?? [],
            ]),
        ];
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<T>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<T>
     */
    private function filter(Builder $query, array $filters, ?Boat $boat): Builder
    {
        $depth = max((float) ($filters['min_depth'] ?? 0), $boat === null ? 0 : $boat->draft + 0.5);

        return $query->when($depth > 0, fn ($query) => $query->where('min_depth_meters', '>=', $depth));
    }
}
