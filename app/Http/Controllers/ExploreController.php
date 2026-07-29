<?php

namespace App\Http\Controllers;

use App\Models\Anchorage;
use App\Models\Port;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        $spots = [
            ...($type === 'anchorage' ? [] : $this->searchPorts($search)),
            ...($type === 'port' ? [] : $this->searchAnchorages($search)),
        ];

        usort($spots, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return Inertia::render('explore', [
            'filters' => ['q' => $search, 'type' => $type],
            'spots' => array_slice($spots, 0, 60),
            'counts' => [
                'ports' => Port::count(),
                'anchorages' => Anchorage::count(),
            ],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function searchPorts(string $search): array
    {
        return Port::query()
            ->when($search !== '', fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']))
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(fn (Port $port): array => [
                'slug' => $port->slug,
                'name' => $port->name,
                'type' => 'port',
                'region' => $port->region,
                'country_code' => $port->country_code,
                'latitude' => $port->latitude,
                'longitude' => $port->longitude,
                'depth' => $port->min_depth_meters,
                'rating' => $port->reviews_avg_rating === null ? null : round((float) $port->reviews_avg_rating, 1),
                'reviews_count' => $port->reviews_count,
                'services' => $port->services(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function searchAnchorages(string $search): array
    {
        return Anchorage::query()
            ->when($search !== '', fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']))
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(fn (Anchorage $anchorage): array => [
                'slug' => $anchorage->slug,
                'name' => $anchorage->name,
                'type' => 'anchorage',
                'region' => $anchorage->region,
                'country_code' => $anchorage->country_code,
                'latitude' => $anchorage->latitude,
                'longitude' => $anchorage->longitude,
                'depth' => $anchorage->depthLabel(),
                'rating' => $anchorage->reviews_avg_rating === null ? null : round((float) $anchorage->reviews_avg_rating, 1),
                'reviews_count' => $anchorage->reviews_count,
                'tags' => $anchorage->tags ?? [],
            ])
            ->values()
            ->all();
    }
}
