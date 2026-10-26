<?php

namespace Tests\Feature;

use App\Jobs\GenerateCruiseRoutes;
use App\Models\Cruise;
use App\Services\Routing\RouteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\Concerns\AuthenticatesAccount;
use Tests\TestCase;

class RouteGenerationJobTest extends TestCase
{
    use AuthenticatesAccount;
    use RefreshDatabase;

    public function test_the_queue_does_not_retry_while_a_long_route_job_can_still_be_running(): void
    {
        $job = new GenerateCruiseRoutes(1, (string) Str::uuid());
        $this->assertGreaterThan($job->timeout, config('queue.connections.database.retry_after'));
    }

    public function test_generation_job_records_a_failed_empty_result(): void
    {
        $token = (string) Str::uuid();
        $cruise = Cruise::create([
            'user_id' => $this->accountUser->id,
            'organization_id' => $this->accountOrganization->id,
            'slug' => 'job-test',
            'title' => 'Job test',
            'departure_port' => 'Brest',
            'destination_port' => 'Lorient',
            'departure_date' => now()->addWeek(),
            'duration_days' => 4,
            'crew_count' => 2,
            'navigation_style' => 'Confort',
            'route_generation_status' => 'pending',
            'route_generation_token' => $token,
        ]);
        $generator = $this->mock(RouteGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')->once()->andReturn([]);
        });

        (new GenerateCruiseRoutes($cruise->id, $token))->handle($generator);

        $this->assertSame('failed', $cruise->fresh()->route_generation_status);
        $this->assertNotNull($cruise->fresh()->route_generation_completed_at);
    }
}
