<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use App\Models\RouteSegment;
use App\Services\Accounts\CurrentContext;
use App\Services\Safety\SafetyAuditor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SafetyAuditor $auditor,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $cruise = $this->context->cruise($user);
        $boat = $this->context->boat($user, $cruise);

        $audit = $this->auditor->audit($boat, $cruise);

        return Inertia::render('dashboard', [
            'dashboardData' => [
                'boat' => $boat === null ? null : [
                    'name' => $boat->name,
                    'model' => $boat->model,
                    'type' => $boat->boat_type,
                    'draft' => $boat->draft,
                    'mast_height' => $boat->mast_height,
                ],
                'cruise' => $this->cruisePayload($cruise),
                'readiness' => $audit['readiness'],
                'readiness_label' => $audit['readiness_label'],
                'missing_equipment' => $audit['missing'],
                'alerts' => $this->alerts($request),
            ],
        ]);
    }

    /** @return array<string, mixed>|null */
    private function cruisePayload(?Cruise $cruise): ?array
    {
        if ($cruise === null) {
            return null;
        }

        $route = $cruise->selectedRoute;

        return [
            'slug' => $cruise->slug,
            'title' => $cruise->title,
            'departure_port' => $cruise->departure_port,
            'destination_port' => $cruise->destination_port,
            'departure_date' => $cruise->departure_date?->translatedFormat('d F Y'),
            'duration_days' => $cruise->duration_days,
            'crew_count' => $cruise->crew_count,
            'style' => $cruise->navigation_style,
            'status' => $cruise->status,
            'route' => $route === null ? null : [
                'scenario' => $route->scenario,
                'label' => $route->label,
                'score' => $route->score,
                'score_breakdown' => $route->score_breakdown,
                'distance_nm' => $route->distance_nm,
                'stops' => $route->stops,
                'geometry' => $route->geometry ?? [],
                'available' => count($route->geometry ?? []) > 1,
                'computed_at' => $route->computed_at?->toIso8601String(),
            ],
            'next_legs' => $route === null ? [] : $route->segments->take(4)->map(fn (RouteSegment $segment): array => [
                'day' => $segment->day,
                'from' => $segment->from_name,
                'to' => $segment->to_name,
                'distance_nm' => $segment->distance_nm,
                'duration_hours' => $segment->duration_hours,
                'overnight' => $segment->overnight,
                'weather' => $segment->weather_snapshot,
            ])->all(),
        ];
    }

    /**
     * Alertes réelles : notifications non lues de l'utilisateur.
     *
     * @return list<array<string, mixed>>
     */
    private function alerts(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $alerts = [];

        foreach ($user->unreadNotifications->take(5) as $notification) {
            $data = $notification->data;

            $alerts[] = [
                'id' => $notification->id,
                'type' => $data['type'] ?? 'info',
                'message' => $this->alertMessage($data),
                'created_at' => $notification->created_at?->diffForHumans(),
            ];
        }

        return $alerts;
    }

    /** @param array<string, mixed> $data */
    private function alertMessage(array $data): string
    {
        if (($data['type'] ?? null) === 'weather_alert') {
            $legs = $data['legs'] ?? [];
            $first = $legs[0] ?? null;

            return $first === null
                ? 'La météo évolue sur votre croisière.'
                : sprintf(
                    'Météo dégradée sur J%d — %s → %s : %s.',
                    $first['day'] ?? 0,
                    $first['from'] ?? '',
                    $first['to'] ?? '',
                    $first['label'] ?? '',
                );
        }

        return (string) ($data['message'] ?? 'Nouvelle notification.');
    }
}
