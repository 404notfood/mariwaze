<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Cruise;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'organizations' => Organization::query()->count(),
                'boats' => Boat::query()->count(),
                'cruises' => Cruise::query()->count(),
            ],
            'catalog' => [
                'plans' => Plan::query()->count(),
                'active_plans' => Plan::query()->where('is_active', true)->count(),
                'features' => PlanFeature::query()->count(),
                'billable_options' => PlanFeature::query()->where('is_billable_option', true)->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }
}
