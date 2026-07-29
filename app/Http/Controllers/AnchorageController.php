<?php

namespace App\Http\Controllers;

use App\Models\Anchorage;
use App\Models\Review;
use App\Services\Accounts\CurrentContext;
use App\Services\Routing\BoatCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnchorageController extends Controller
{
    public function __construct(
        private readonly BoatCompatibilityService $compatibility,
        private readonly CurrentContext $context,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $anchorage = Anchorage::query()
            ->with(['reviews' => fn ($query) => $query->with('user')->latest()->limit(10)])
            ->withCount(['reviews', 'photos'])
            ->withAvg('reviews', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        $boat = $this->context->boat($request->user());
        $check = $boat === null ? null : $this->compatibility->forAnchorage($boat, $anchorage);

        return Inertia::render('anchorage-show', [
            'anchorage' => [
                'slug' => $anchorage->slug,
                'name' => $anchorage->name,
                'region' => $anchorage->region,
                'country_code' => $anchorage->country_code,
                'latitude' => $anchorage->latitude,
                'longitude' => $anchorage->longitude,
                'rating' => $anchorage->reviews_avg_rating === null ? null : round((float) $anchorage->reviews_avg_rating, 1),
                'reviews_count' => $anchorage->reviews_count,
                'photos_count' => $anchorage->photos_count,
                'depth' => $anchorage->depthLabel(),
                'bottom' => $anchorage->bottom_type,
                'holding' => $anchorage->holding,
                'protection' => $anchorage->protected_from ?? [],
                'exposure' => $anchorage->exposure,
                'tags' => $anchorage->tags ?? [],
                'warnings' => $anchorage->warnings ?? [],
                'description' => $anchorage->description,
                'source' => $anchorage->source,
                'compatibility' => $check === null ? null : [
                    'compatible' => $check['compatible'],
                    'score' => $check['score'],
                    'reasons' => $check['reasons'],
                ],
                'reviews' => $anchorage->reviews->map(fn (Review $review): array => [
                    'id' => $review->id,
                    'author' => $review->user === null ? 'Navigateur' : $review->user->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'visited_on' => $review->visited_on?->translatedFormat('F Y'),
                ])->all(),
            ],
        ]);
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $anchorage = Anchorage::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
            'visited_on' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $anchorage->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return back()->with('success', 'Merci pour votre avis.');
    }
}
