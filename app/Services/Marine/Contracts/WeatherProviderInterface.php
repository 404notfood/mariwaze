<?php

namespace App\Services\Marine\Contracts;

use App\Exceptions\MarineConditionsUnavailable;

/**
 * Fournisseur de conditions marines.
 *
 * L'abstraction permet de changer de source (Open-Meteo aujourd'hui, Météo-France
 * ou Windy plus tard) sans toucher au moteur de routage ni aux contrôleurs.
 */
interface WeatherProviderInterface
{
    /**
     * Conditions courantes à un point donné.
     *
     * @return array<string, mixed>
     *
     * @throws MarineConditionsUnavailable
     */
    public function conditions(float $latitude, float $longitude): array;

    /**
     * Prévision horaire utilisée pour scorer une étape.
     *
     * @return list<array{time: string, wind_knots: float|null, gusts_knots: float|null, wave_height_meters: float|null}>
     *
     * @throws MarineConditionsUnavailable
     */
    public function forecast(float $latitude, float $longitude, \DateTimeInterface $from, int $hours = 24): array;
}
