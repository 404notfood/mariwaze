<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        $reviews = Review::query()
            ->with(['user:id,name,email', 'reviewable'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/reviews', [
            'reviews' => [
                'data' => $reviews->getCollection()->map(fn (Review $review): array => [
                    'id' => $review->id,
                    'author' => $review->user->name ?? 'Compte supprimé',
                    'email' => $review->user->email ?? null,
                    'place' => $review->reviewable?->getAttribute('name') ?? 'Lieu supprimé',
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'status' => $review->status->value,
                    'created_at' => $review->created_at?->toIso8601String(),
                ])->all(),
                'links' => $reviews->linkCollection()->all(),
                'total' => $reviews->total(),
            ],
            'pendingCount' => Review::query()->where('status', ReviewStatus::Pending)->count(),
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReviewStatus::class), Rule::notIn([ReviewStatus::Pending->value])],
        ]);
        $before = ['status' => $review->status->value];

        $review->update([
            'status' => ReviewStatus::from($data['status']),
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        $this->audit->record($request, 'admin.review.moderated', $review, $before, [
            'status' => $review->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Avis modéré.']);

        return back();
    }
}
