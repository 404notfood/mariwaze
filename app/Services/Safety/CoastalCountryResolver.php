<?php

namespace App\Services\Safety;

use App\Support\GeoScope;

/**
 * Déduit le pays côtier le plus proche d'un point de navigation.
 *
 * Les données OpenStreetMap importées ne portent presque jamais de code pays :
 * ce résolveur s'appuie sur une grille de points côtiers de référence
 * (caps, grands ports) d'Europe de l'Ouest et de Méditerranée occidentale.
 * Au-delà de MAX_DISTANCE_NM de tout point de référence (plein océan), aucun
 * pays n'est retourné.
 */
class CoastalCountryResolver
{
    private const MAX_DISTANCE_NM = 90.0;

    /** @var list<array{string, float, float}> [code pays, latitude, longitude] */
    private const REFERENCE_POINTS = [
        // France — Manche et Atlantique
        ['FR', 51.05, 2.37], ['FR', 49.49, 0.10], ['FR', 49.65, -1.62], ['FR', 48.65, -2.03],
        ['FR', 48.86, -3.00], ['FR', 48.39, -4.49], ['FR', 48.46, -5.10], ['FR', 47.75, -3.37],
        ['FR', 47.27, -2.20], ['FR', 46.16, -1.15], ['FR', 44.66, -1.16], ['FR', 43.49, -1.48],
        // France — Méditerranée et Corse
        ['FR', 42.52, 3.11], ['FR', 43.40, 3.70], ['FR', 43.30, 5.37], ['FR', 43.12, 5.93],
        ['FR', 43.70, 7.27], ['FR', 41.92, 8.74], ['FR', 42.70, 9.45], ['FR', 41.39, 9.16],
        // Espagne — golfe de Gascogne et Galice
        ['ES', 43.26, -3.03], ['ES', 43.46, -3.80], ['ES', 43.54, -5.66], ['ES', 43.37, -8.40],
        ['ES', 42.91, -9.26], ['ES', 42.24, -8.72],
        // Espagne — Andalousie, Levant et Baléares
        ['ES', 36.53, -6.29], ['ES', 36.01, -5.60], ['ES', 36.71, -4.42], ['ES', 36.83, -2.46],
        ['ES', 37.60, -0.98], ['ES', 38.34, -0.48], ['ES', 39.47, -0.33], ['ES', 41.38, 2.18],
        ['ES', 39.57, 2.65], ['ES', 38.91, 1.43], ['ES', 39.89, 4.26],
        // Portugal
        ['PT', 41.69, -8.83], ['PT', 41.15, -8.68], ['PT', 40.64, -8.66], ['PT', 39.36, -9.38],
        ['PT', 38.70, -9.18], ['PT', 37.95, -8.87], ['PT', 37.01, -8.93], ['PT', 37.02, -7.93],
        // Royaume-Uni et îles Anglo-Normandes
        ['GB', 50.37, -4.14], ['GB', 50.15, -5.07], ['GB', 50.80, -1.09], ['GB', 51.13, 1.32],
        ['GB', 49.46, -2.59],
        // Irlande
        ['IE', 51.85, -8.30],
        // Italie et Sardaigne
        ['IT', 44.41, 8.93], ['IT', 43.55, 10.31], ['IT', 42.09, 11.79], ['IT', 40.84, 14.25],
        ['IT', 39.22, 9.11], ['IT', 40.92, 9.50], ['IT', 38.13, 13.34],
        // Belgique et Pays-Bas
        ['BE', 51.23, 2.92], ['NL', 51.95, 4.05], ['NL', 52.96, 4.76],
        // Gibraltar et Maroc (rives du détroit)
        ['GI', 36.14, -5.35], ['MA', 35.79, -5.81],
    ];

    public function resolve(float $latitude, float $longitude): ?string
    {
        $nearest = null;
        $minimum = INF;

        foreach (self::REFERENCE_POINTS as [$code, $refLatitude, $refLongitude]) {
            $distance = GeoScope::nauticalMiles($latitude, $longitude, $refLatitude, $refLongitude);

            if ($distance < $minimum) {
                $minimum = $distance;
                $nearest = $code;
            }
        }

        return $minimum <= self::MAX_DISTANCE_NM ? $nearest : null;
    }
}
