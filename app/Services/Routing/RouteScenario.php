<?php

namespace App\Services\Routing;

/**
 * Scénarios de route proposés à l'utilisateur (doc/routage.md, écran 4-5).
 *
 * Chaque scénario ajuste la longueur des étapes et le confort attendu :
 * « rapide » enchaîne de longues journées, « découverte » multiplie les escales.
 */
enum RouteScenario: string
{
    case Fast = 'fast';
    case Comfort = 'comfort';
    case Discovery = 'discovery';

    public function label(): string
    {
        return match ($this) {
            self::Fast => 'Rapide',
            self::Comfort => 'Confort',
            self::Discovery => 'Découverte',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Fast => 'Étapes longues, trajet le plus direct.',
            self::Comfort => 'Journées équilibrées et escales bien protégées.',
            self::Discovery => 'Étapes courtes et nombreuses escales à visiter.',
        };
    }

    /** Durée maximale de navigation par jour, en heures. */
    public function maxHoursPerDay(): float
    {
        return match ($this) {
            self::Fast => 10.0,
            self::Comfort => 7.0,
            self::Discovery => 5.0,
        };
    }

    /** Poids du confort dans le score final, à distance égale. */
    public function comfortBias(): int
    {
        return match ($this) {
            self::Fast => 45,
            self::Comfort => 85,
            self::Discovery => 75,
        };
    }
}
