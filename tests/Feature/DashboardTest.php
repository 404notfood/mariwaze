<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_dashboard_is_available_on_the_normal_development_url()
    {
        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
