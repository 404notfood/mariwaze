<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Http\Requests\ReviewRequest;
use App\Models\Port;
use App\Models\Review;
use App\Services\Accounts\CurrentContext;
use App\Services\Routing\BoatCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortController extends Controller
{
    public function __construct(
        private readonly BoatCompatibilityService $compatibility,
        private readonly CurrentContext $context,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $port = Port::query()
            ->with(['publicReviews' => fn ($query) => $query->with('user')->latest()->limit(10)])
            ->withCount(['publicReviews as reviews_count', 'photos'])
            ->withAvg('publicReviews as reviews_avg_rating', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        $boat = $this->context->boat($request->user());
        $check = $boat === null ? null : $this->compatibility->forPort($boat, $port);

        return Inertia::render('port-show', [
            'port' => [
                'slug' => $port->slug,
                'name' => $port->name,
                'region' => $port->region,
                'country_code' => $port->country_code,
                'latitude' => $port->latitude,
                'longitude' => $port->longitude,
                'rating' => $port->reviews_avg_rating === null ? null : round((float) $port->reviews_avg_rating, 1),
                'reviews_count' => $port->reviews_count,
                'photos_count' => $port->photos_count,
                'min_depth_meters' => $port->min_depth_meters,
                'max_length_meters' => $port->max_length_meters,
                'max_width_meters' => $port->max_width_meters,
                'berths' => $port->berths,
                'services' => $port->services(),
                'vhf_channel' => $port->vhf_channel,
                'phone' => $port->phone,
                'website' => $port->website,
                'night_price_eur' => $port->night_price_eur,
                'description' => $port->description,
                'source' => $port->source,
                'compatibility' => $check === null ? null : [
                    'compatible' => $check['compatible'],
                    'score' => $check['score'],
                    'reasons' => $check['reasons'],
                ],
                'reviews' => $port->publicReviews->map(fn (Review $review): array => [
                    'id' => $review->id,
                    'author' => $review->user === null ? 'Navigateur' : $review->user->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'visited_on' => $review->visited_on?->translatedFormat('F Y'),
                ])->all(),
            ],
        ]);
    }

    public function storeReview(ReviewRequest $request, string $slug): RedirectResponse
    {
        $port = Port::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validated();

        $port->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [...$validated, 'status' => ReviewStatus::Pending],
        );

        return back()->with('success', 'Merci. Votre avis sera visible après modération.');
    }
}
