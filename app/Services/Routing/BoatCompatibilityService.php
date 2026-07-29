<?php

namespace App\Services\Routing;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Port;

/**
 * Filtre de compatibilité bateau ↔ escale (doc/moteur-de-routage.md).
 *
 * Une escale est incompatible dès qu'une contrainte physique du bateau
 * (tirant d'eau, longueur, largeur) dépasse ce que l'escale accepte.
 */
class BoatCompatibilityService
{
    /** Marge de sécurité sous la quille, en mètres. */
    public const DRAFT_MARGIN_METERS = 0.5;

    /**
     * @return array{compatible: bool, score: int, reasons: list<string>}
     */
    public function forPort(Boat $boat, Port $port): array
    {
        $reasons = [];
        $score = 100;

        $depth = $port->min_depth_meters;
        if ($depth !== null) {
            $required = $boat->draft + self::DRAFT_MARGIN_METERS;

            if ($depth < $boat->draft) {
                $reasons[] = sprintf('Profondeur %.1f m insuffisante pour un tirant d\'eau de %.1f m.', $depth, $boat->draft);
                $score -= 60;
            } elseif ($depth < $required) {
                $reasons[] = sprintf('Marge sous quille réduite : %.1f m pour %.1f m de tirant d\'eau.', $depth, $boat->draft);
                $score -= 20;
            }
        }

        if ($port->max_length_meters !== null && $boat->length > $port->max_length_meters) {
            $reasons[] = sprintf('Longueur %.1f m supérieure au maximum accepté (%.1f m).', $boat->length, $port->max_length_meters);
            $score -= 50;
        }

        if ($port->max_width_meters !== null && $boat->width > $port->max_width_meters) {
            $reasons[] = sprintf('Largeur %.1f m supérieure au maximum accepté (%.1f m).', $boat->width, $port->max_width_meters);
            $score -= 40;
        }

        return [
            'compatible' => $score >= 50,
            'score' => max(0, min(100, $score)),
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array{compatible: bool, score: int, reasons: list<string>}
     */
    public function forAnchorage(Boat $boat, Anchorage $anchorage): array
    {
        $reasons = [];
        $score = 100;

        $depth = $anchorage->min_depth_meters;
        if ($depth !== null) {
            if ($depth < $boat->draft) {
                $reasons[] = sprintf('Fond de %.1f m insuffisant pour un tirant d\'eau de %.1f m.', $depth, $boat->draft);
                $score -= 60;
            } elseif ($depth < $boat->draft + self::DRAFT_MARGIN_METERS) {
                $reasons[] = sprintf('Mouillage limite : %.1f m de fond pour %.1f m de tirant d\'eau.', $depth, $boat->draft);
                $score -= 20;
            }
        }

        if ($anchorage->holding !== null && str_contains(mb_strtolower($anchorage->holding), 'poor')) {
            $reasons[] = 'Tenue du mouillage signalée comme médiocre.';
            $score -= 25;
        }

        return [
            'compatible' => $score >= 50,
            'score' => max(0, min(100, $score)),
            'reasons' => $reasons,
        ];
    }
}
