<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class BillingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $features = collect([
            ['name' => 'Navigation cotiere', 'slug' => 'coastal-navigation', 'category' => 'navigation'],
            ['name' => 'Navigation hauturiere', 'slug' => 'offshore-navigation', 'category' => 'navigation', 'is_billable_option' => true],
            ['name' => 'Navigation fluviale', 'slug' => 'inland-navigation', 'category' => 'navigation', 'is_billable_option' => true],
            ['name' => 'Exports PDF', 'slug' => 'pdf-exports', 'category' => 'exports'],
            ['name' => 'Checklist avitaillement', 'slug' => 'provisioning-checklist', 'category' => 'exports'],
            ['name' => 'Gestion de flotte', 'slug' => 'fleet-management', 'category' => 'pro', 'is_billable_option' => true],
            ['name' => 'Meteo avancee', 'slug' => 'advanced-weather', 'category' => 'navigation', 'is_billable_option' => true],
        ])->mapWithKeys(fn (array $feature) => [
            $feature['slug'] => PlanFeature::updateOrCreate(
                ['slug' => $feature['slug']],
                [
                    'name' => $feature['name'],
                    'category' => $feature['category'],
                    'is_billable_option' => $feature['is_billable_option'] ?? false,
                ],
            ),
        ]);

        $plans = [
            [
                'attributes' => ['slug' => 'free'],
                'values' => ['name' => 'Gratuit', 'audience' => 'personal', 'monthly_price_cents' => 0, 'boat_limit' => 1, 'member_limit' => 1],
                'features' => ['coastal-navigation', 'provisioning-checklist'],
            ],
            [
                'attributes' => ['slug' => 'plaisancier'],
                'values' => ['name' => 'Plaisancier', 'audience' => 'personal', 'monthly_price_cents' => 900, 'yearly_price_cents' => 9000, 'boat_limit' => 3, 'member_limit' => 2],
                'features' => ['coastal-navigation', 'pdf-exports', 'provisioning-checklist', 'advanced-weather'],
            ],
            [
                'attributes' => ['slug' => 'hauturier'],
                'values' => ['name' => 'Hauturier', 'audience' => 'personal', 'monthly_price_cents' => 1900, 'yearly_price_cents' => 19000, 'boat_limit' => 5, 'member_limit' => 4],
                'features' => ['coastal-navigation', 'offshore-navigation', 'pdf-exports', 'provisioning-checklist', 'advanced-weather'],
            ],
            [
                'attributes' => ['slug' => 'fluvial'],
                'values' => ['name' => 'Fluvial', 'audience' => 'personal', 'monthly_price_cents' => 1200, 'yearly_price_cents' => 12000, 'boat_limit' => 3, 'member_limit' => 2],
                'features' => ['inland-navigation', 'pdf-exports', 'provisioning-checklist'],
            ],
            [
                'attributes' => ['slug' => 'loueur-pro'],
                'values' => ['name' => 'Loueur Pro', 'audience' => 'business', 'monthly_price_cents' => 4900, 'yearly_price_cents' => 49000, 'boat_limit' => null, 'member_limit' => null],
                'features' => ['coastal-navigation', 'offshore-navigation', 'inland-navigation', 'pdf-exports', 'provisioning-checklist', 'fleet-management', 'advanced-weather'],
            ],
        ];

        foreach ($plans as $definition) {
            $plan = Plan::updateOrCreate($definition['attributes'], $definition['values']);
            $plan->features()->sync($features->only($definition['features'])->pluck('id')->all());
        }
    }
}
