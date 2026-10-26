<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Cruise;
use App\Services\Safety\SafetyAuditor;
use Database\Seeders\MaritimeSafetySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class SafetyAuditTest extends TestCase
{
    use AuthenticatesAccount, RefreshDatabase;

    public function test_a_fully_equipped_coastal_boat_is_ready(): void
    {
        $boat = new Boat([
            'name' => 'Éole', 'boat_type' => 'voilier', 'length' => 12.0, 'width' => 4.0,
            'draft' => 2.0, 'avg_speed' => 6.5,
            'equipment_json' => [
                'lifejackets' => true, 'flares' => true, 'fire_extinguisher' => true,
                'first_aid' => true, 'vhf' => true, 'bilge_pump' => true,
            ],
        ]);

        $audit = app(SafetyAuditor::class)->audit($boat);

        $this->assertSame(100, $audit['readiness']);
        $this->assertSame([], $audit['missing']);
    }

    public function test_missing_equipment_is_reported(): void
    {
        $boat = new Boat([
            'name' => 'Éole', 'boat_type' => 'voilier', 'length' => 12.0, 'width' => 4.0,
            'draft' => 2.0, 'avg_speed' => 6.5,
            'equipment_json' => ['vhf' => true, 'lifejackets' => true],
        ]);

        $audit = app(SafetyAuditor::class)->audit($boat);

        $this->assertLessThan(100, $audit['readiness']);
        $this->assertContains('Extincteurs', $audit['missing']);
        $this->assertContains('Pompe de cale', $audit['missing']);
    }

    public function test_offshore_navigation_requires_more_equipment(): void
    {
        $equipment = [
            'lifejackets' => true, 'flares' => true, 'fire_extinguisher' => true,
            'first_aid' => true, 'vhf' => true, 'bilge_pump' => true,
        ];

        $boat = new Boat([
            'name' => 'Éole', 'boat_type' => 'voilier', 'length' => 12.0, 'width' => 4.0,
            'draft' => 2.0, 'avg_speed' => 6.5, 'equipment_json' => $equipment,
        ]);

        $coastal = new Cruise(['navigation_area' => 'coastal']);
        $offshore = new Cruise(['navigation_area' => 'offshore']);

        $auditor = app(SafetyAuditor::class);

        $this->assertSame(100, $auditor->audit($boat, $coastal)['readiness']);
        // Radeau, EPIRB et AIS manquent pour le hauturier.
        $this->assertLessThan(100, $auditor->audit($boat, $offshore)['readiness']);
    }

    public function test_a_boat_without_equipment_has_zero_readiness(): void
    {
        $audit = app(SafetyAuditor::class)->audit(null);

        $this->assertSame(0, $audit['readiness']);
        $this->assertNull($audit['boat']);
    }

    public function test_the_security_page_uses_real_emergency_contacts(): void
    {
        $this->seed(MaritimeSafetySeeder::class);

        $this->get('/securite')
            ->assertInertia(fn (Assert $page) => $page
                ->component('security')
                ->has('securityData.checklist')
                ->has('securityData.emergency')
            );
    }

    public function test_emergency_contacts_are_seeded_for_france(): void
    {
        $this->seed(MaritimeSafetySeeder::class);

        $this->assertDatabaseHas('emergency_contacts', ['country_code' => 'FR', 'phone' => '196']);
        $this->assertDatabaseHas('emergency_contacts', ['country_code' => 'FR', 'vhf_channel' => '16']);
        $this->assertDatabaseHas('country_regulations', ['country_code' => 'FR']);
    }
}
