<?php

namespace App\Services\Routing;

/**
 * Score global d'une route candidate (doc/routage.md).
 *
 * Pondération : météo 35 %, compatibilité bateau 25 %, sécurité 20 %,
 * confort 10 %, communauté 10 %.
 */
class RouteScorer
{
    /** @var array<string, float> */
    public const WEIGHTS = [
        'weather' => 0.35,
        'boat' => 0.25,
        'safety' => 0.20,
        'comfort' => 0.10,
        'community' => 0.10,
    ];

    /**
     * @param  array{weather?: int, boat?: int, safety?: int, comfort?: int, community?: int}  $components
     * @return array{score: int, breakdown: array<string, array{value: int, weight: float, points: float}>}
     */
    public function score(array $components): array
    {
        $breakdown = [];
        $total = 0.0;

        foreach (self::WEIGHTS as $key => $weight) {
            $value = max(0, min(100, (int) ($components[$key] ?? 50)));
            $points = $value * $weight;
            $total += $points;

            $breakdown[$key] = [
                'value' => $value,
                'weight' => $weight,
                'points' => round($points, 1),
            ];
        }

        return [
            'score' => (int) round($total),
            'breakdown' => $breakdown,
        ];
    }

    public function label(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Bon',
            $score >= 50 => 'Acceptable',
            $score >= 30 => 'À revoir',
            default => 'Déconseillé',
        };
    }
}
