<?php

namespace App\Jobs;

use App\Models\Cruise;
use App\Services\Routing\RouteGenerator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCruiseRoutes implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $cruiseId,
        public readonly string $token,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->cruiseId;
    }

    public function handle(RouteGenerator $generator): void
    {
        $cruise = Cruise::query()->with('boat')->find($this->cruiseId);

        if ($cruise === null || $cruise->route_generation_token !== $this->token) {
            return;
        }

        $cruise->update([
            'route_generation_status' => 'running',
            'route_generation_started_at' => now(),
            'route_generation_error' => null,
        ]);

        $routes = $generator->generate($cruise);

        if ($routes === []) {
            Log::warning('Cruise route generation returned no route', [
                'cruise_id' => $cruise->id,
            ]);
        }

        $cruise->update([
            'route_generation_status' => $routes === [] ? 'failed' : 'completed',
            'route_generation_error' => $routes === []
                ? "Aucun itinéraire navigable n'a pu être calculé."
                : null,
            'route_generation_completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cruise route generation failed', [
            'cruise_id' => $this->cruiseId,
            'exception_type' => $exception === null ? null : $exception::class,
        ]);

        Cruise::query()
            ->whereKey($this->cruiseId)
            ->where('route_generation_token', $this->token)
            ->update([
                'route_generation_status' => 'failed',
                'route_generation_error' => 'Le calcul a échoué après plusieurs tentatives.',
                'route_generation_completed_at' => now(),
            ]);
    }
}
