<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\BillingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_plans_and_billable_options_are_seeded(): void
    {
        $this->seed(BillingCatalogSeeder::class);

        $pro = Plan::query()->where('slug', 'loueur-pro')->firstOrFail();

        $this->assertSame('business', $pro->audience);
        $this->assertNull($pro->boat_limit);
        $this->assertTrue($pro->features()->where('slug', 'fleet-management')->exists());
        $this->assertTrue($pro->features()->where('slug', 'offshore-navigation')->exists());
        $this->assertTrue($pro->features()->where('slug', 'inland-navigation')->exists());
    }
}
