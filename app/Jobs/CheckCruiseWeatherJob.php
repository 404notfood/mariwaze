<?php

namespace App\Jobs;

use App\Models\Cruise;
use App\Models\User;
use App\Notifications\CruiseWeatherAlert;
use App\Services\Routing\WeatherAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Surveille la météo des croisières à venir (doc/mvp-tech.md).
 *
 * Le job réévalue chaque étape de la route sélectionnée et notifie le
 * propriétaire quand une étape devient défavorable.
 */
class CheckCruiseWeatherJob implements ShouldQueue
{
    use Queueable;

    /** En dessous de ce score, l'étape déclenche une alerte. */
    public const ALERT_THRESHOLD = 45;

    public function __construct(public readonly int $cruiseId) {}

    public function handle(WeatherAnalyzer $analyzer): void
    {
        $cruise = Cruise::query()
            ->with(['selectedRoute.segments'])
            ->find($this->cruiseId);

        if ($cruise === null) {
            return;
        }

        $user = User::query()->find($cruise->user_id);

        if ($user === null) {
            return;
        }

        $route = $cruise->selectedRoute;

        if ($route === null) {
            return;
        }

        $departure = $cruise->departure_date;

        if ($departure === null || $departure->isPast()) {
            return;
        }

        $degraded = [];

        foreach ($route->segments as $segment) {
            if ($segment->to_latitude === null || $segment->to_longitude === null) {
                continue;
            }

            $score = $analyzer->scoreLeg(
                $segment->to_latitude,
                $segment->to_longitude,
                $departure->copy()->addDays(max(0, $segment->day - 1))->setTime(8, 0),
                (int) ceil($segment->duration_hours ?? 6),
            );

            if (! $score['available']) {
                continue;
            }

            $previous = $segment->weather_score;
            $segment->update([
                'weather_score' => $score['score'],
                'weather_snapshot' => [
                    'label' => $score['label'],
                    'detail' => $score['detail'],
                    'wind_knots' => $score['wind_knots'],
                    'wave_height_meters' => $score['wave_height_meters'],
                    'available' => true,
                ],
            ]);

            // On alerte sur une dégradation franche, pas sur un score déjà connu comme mauvais.
            if ($score['score'] < self::ALERT_THRESHOLD && ($previous === null || $previous >= self::ALERT_THRESHOLD)) {
                $degraded[] = [
                    'day' => $segment->day,
                    'from' => $segment->from_name,
                    'to' => $segment->to_name,
                    'label' => $score['label'],
                    'detail' => $score['detail'],
                    'score' => $score['score'],
                ];
            }
        }

        if ($degraded === []) {
            return;
        }

        Log::info('Alerte météo croisière', ['cruise_id' => $cruise->id, 'legs' => count($degraded)]);

        $user->notify(new CruiseWeatherAlert($cruise, $degraded));
    }
}
