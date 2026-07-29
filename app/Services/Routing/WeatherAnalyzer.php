<?php

namespace App\Services\Routing;

use App\Exceptions\MarineConditionsUnavailable;
use App\Services\Marine\Contracts\WeatherProviderInterface;
use DateTimeInterface;

/**
 * Note météo d'une étape (doc/routage.md).
 *
 * Le score part de 100 et décroît avec le vent, les rafales et la hauteur de
 * vagues. Les seuils suivent les repères de navigation de plaisance : au-delà
 * de 25 nœuds établis ou 2,5 m de vagues, la sortie devient déconseillée.
 */
class WeatherAnalyzer
{
    public function __construct(private readonly WeatherProviderInterface $weather) {}

    /**
     * @return array{score: int, label: string, detail: string, wind_knots: float|null, wave_height_meters: float|null, available: bool}
     */
    public function scoreLeg(float $latitude, float $longitude, DateTimeInterface $departure, int $hours = 12): array
    {
        try {
            $forecast = $this->weather->forecast($latitude, $longitude, $departure, $hours);
        } catch (MarineConditionsUnavailable) {
            return $this->unavailable();
        }

        if ($forecast === []) {
            return $this->unavailable();
        }

        $winds = array_filter(array_column($forecast, 'wind_knots'), is_numeric(...));
        $gusts = array_filter(array_column($forecast, 'gusts_knots'), is_numeric(...));
        $waves = array_filter(array_column($forecast, 'wave_height_meters'), is_numeric(...));

        $maxWind = $winds === [] ? null : max($winds);
        $maxGust = $gusts === [] ? null : max($gusts);
        $maxWave = $waves === [] ? null : max($waves);

        $score = 100;
        $score -= $this->windPenalty($maxWind);
        $score -= $this->gustPenalty($maxGust);
        $score -= $this->wavePenalty($maxWave);
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => $this->label($score),
            'detail' => $this->detail($maxWind, $maxWave),
            'wind_knots' => $maxWind,
            'wave_height_meters' => $maxWave,
            'available' => true,
        ];
    }

    private function windPenalty(?float $knots): int
    {
        return match (true) {
            $knots === null => 0,
            $knots >= 33 => 70,
            $knots >= 25 => 45,
            $knots >= 20 => 25,
            $knots >= 15 => 10,
            default => 0,
        };
    }

    private function gustPenalty(?float $knots): int
    {
        return match (true) {
            $knots === null => 0,
            $knots >= 40 => 20,
            $knots >= 30 => 10,
            default => 0,
        };
    }

    private function wavePenalty(?float $meters): int
    {
        return match (true) {
            $meters === null => 0,
            $meters >= 3.0 => 45,
            $meters >= 2.5 => 30,
            $meters >= 1.5 => 15,
            $meters >= 1.0 => 5,
            default => 0,
        };
    }

    private function label(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Très favorable',
            $score >= 70 => 'Favorable',
            $score >= 50 => 'Correct',
            $score >= 30 => 'Attention',
            default => 'Déconseillé',
        };
    }

    private function detail(?float $wind, ?float $wave): string
    {
        $parts = [];

        if ($wind !== null) {
            $parts[] = sprintf('vent jusqu\'à %d nds', (int) round($wind));
        }

        if ($wave !== null) {
            $parts[] = sprintf('vagues %s m', number_format($wave, 1, ',', ' '));
        }

        return $parts === []
            ? 'Prévision marine à actualiser avant le départ.'
            : ucfirst(implode(', ', $parts)).'.';
    }

    /** @return array{score: int, label: string, detail: string, wind_knots: null, wave_height_meters: null, available: false} */
    private function unavailable(): array
    {
        return [
            'score' => 50,
            'label' => 'À confirmer',
            'detail' => 'Prévision marine indisponible : à vérifier avant le départ.',
            'wind_knots' => null,
            'wave_height_meters' => null,
            'available' => false,
        ];
    }
}
