<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/pricing', [
            'plans' => Plan::query()
                ->with('features')
                ->orderBy('monthly_price_cents')
                ->orderBy('name')
                ->get()
                ->map(fn (Plan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'audience' => $plan->audience,
                    'monthly_price_cents' => $plan->monthly_price_cents,
                    'yearly_price_cents' => $plan->yearly_price_cents,
                    'currency' => $plan->currency,
                    'boat_limit' => $plan->boat_limit,
                    'member_limit' => $plan->member_limit,
                    'is_active' => $plan->is_active,
                    'features' => $plan->features->map(fn (PlanFeature $feature) => [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'slug' => $feature->slug,
                        'category' => $feature->category,
                    ])->values()->all(),
                ])->values()->all(),
            'features' => PlanFeature::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(fn (PlanFeature $feature) => [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'category' => $feature->category,
                    'description' => $feature->description,
                    'is_billable_option' => $feature->is_billable_option,
                ])->values()->all(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'audience' => ['required', Rule::in(['personal', 'business'])],
            'monthly_price_cents' => ['required', 'integer', 'min:0', 'max:999900'],
            'yearly_price_cents' => ['nullable', 'integer', 'min:0', 'max:999900'],
            'boat_limit' => ['nullable', 'integer', 'min:1', 'max:999'],
            'member_limit' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_active' => ['required', 'boolean'],
            'feature_ids' => ['array'],
            'feature_ids.*' => ['integer', 'exists:plan_features,id'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'audience' => $data['audience'],
            'monthly_price_cents' => $data['monthly_price_cents'],
            'yearly_price_cents' => $data['yearly_price_cents'],
            'boat_limit' => $data['boat_limit'],
            'member_limit' => $data['member_limit'],
            'is_active' => $data['is_active'],
        ]);

        $plan->features()->sync($data['feature_ids'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarif mis à jour.']);

        return to_route('admin.pricing');
    }
}
