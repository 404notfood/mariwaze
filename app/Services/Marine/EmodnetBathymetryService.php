<?php

namespace App\Services\Marine;

use App\Support\GeoScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Profondeurs le long d'un tracé, via l'API REST EMODnet Bathymetry
 * (DTM européen ~115 m de résolution, données libres).
 *
 * L'API renvoie des altitudes négatives sous le zéro hydrographique : une
 * valeur de -12.4 signifie ~12,4 m d'eau. Les extrémités du tracé aboutissent
 * dans les ports (donc « à terre » pour le modèle) : elles sont exclues du
 * calcul du minimum.
 */
class EmodnetBathymetryService
{
    /** Nombre maximal de sommets envoyés dans la LINESTRING. */
    private const MAX_POINTS = 40;

    /** Distance ignorée à chaque extrémité : approches portuaires (milles). */
    private const APPROACH_NM = 0.8;

    /** Au-dessus de cette altitude, l'échantillon est un artefact côtier. */
    private const LAND_THRESHOLD = -0.3;

    /** Part maximale d'artefacts côtiers avant de juger le profil non fiable. */
    private const MAX_LAND_SHARE = 0.35;

    /**
     * Profondeur minimale (mètres, positive) le long du tracé, ou null si le
     * service est indisponible ou la zone non couverte.
     *
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     */
    public function minDepthAlongPath(array $geometry): ?float
    {
        if (count($geometry) < 2) {
            return null;
        }

        $points = $this->sample($geometry);
        $rounded = array_map(fn (array $point): array => [round($point['latitude'], 4), round($point['longitude'], 4)], $points);
        $key = 'emodnet:profile:'.md5(json_encode($rounded, JSON_THROW_ON_ERROR));

        $depths = Cache::remember($key, now()->addDays(30), function () use ($points): array {
            $wkt = 'LINESTRING('.implode(',', array_map(
                fn (array $point): string => sprintf('%F %F', $point['longitude'], $point['latitude']),
                $points,
            )).')';

            $response = Http::timeout((int) config('services.emodnet.timeout'))
                ->get(config('services.emodnet.url').'/depth_profile', ['geom' => $wkt]);

            if (! $response->successful()) {
                return [];
            }

            $values = $response->json();

            if (! is_array($values)) {
                return [];
            }

            return array_map(
                fn ($value): float => (float) $value,
                array_values(array_filter($values, 'is_numeric')),
            );
        });

        if ($depths === []) {
            return null;
        }

        return $this->shallowestReliableDepth($depths, $points);
    }

    /**
     * Profondeur minimale robuste : les échantillons sont géolocalisés le long
     * de la ligne (répartition uniforme), les approches portuaires sont
     * exclues, les altitudes « terre » sont écartées, et un haut-fond n'est
     * retenu que s'il s'étend sur des échantillons voisins (un pic isolé est
     * un artefact du MNT au ras de la côte).
     *
     * @param  list<float>  $depths
     * @param  list<array{latitude: float, longitude: float}>  $points
     */
    private function shallowestReliableDepth(array $depths, array $points): ?float
    {
        $total = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $total += GeoScope::nauticalMiles(
                $points[$i - 1]['latitude'],
                $points[$i - 1]['longitude'],
                $points[$i]['latitude'],
                $points[$i]['longitude'],
            );
        }

        if ($total <= 0.0) {
            return null;
        }

        $count = count($depths);
        $landish = 0;
        $candidates = [];

        foreach ($depths as $index => $value) {
            $fromStart = $count === 1 ? 0.0 : $total * $index / ($count - 1);

            if ($fromStart < self::APPROACH_NM || $total - $fromStart < self::APPROACH_NM) {
                continue;
            }

            if ($value >= self::LAND_THRESHOLD) {
                $landish++;

                continue;
            }

            $candidates[$index] = (float) $value;
        }

        if ($candidates === []) {
            return null;
        }

        if ($landish / ($landish + count($candidates)) > self::MAX_LAND_SHARE) {
            return null;
        }

        // Un haut-fond n'est retenu que s'il couvre deux échantillons adjacents
        // (~200-300 m) : la valeur de la paire est celle du plus profond des
        // deux, ce qui écarte les pics isolés du MNT au ras de la côte.
        $shallowest = null;

        foreach ($candidates as $index => $value) {
            if (isset($candidates[$index + 1])) {
                $pair = min($value, $candidates[$index + 1]);
                $shallowest = $shallowest === null ? $pair : max($shallowest, $pair);
            }
        }

        return $shallowest === null ? null : round(max(0.0, -$shallowest), 1);
    }

    /**
     * Échantillonne le tracé pour tenir dans une seule requête.
     *
     * @param  list<array{latitude: float, longitude: float}>  $geometry
     * @return list<array{latitude: float, longitude: float}>
     */
    private function sample(array $geometry): array
    {
        $count = count($geometry);

        if ($count <= self::MAX_POINTS) {
            return $geometry;
        }

        $step = ($count - 1) / (self::MAX_POINTS - 1);
        $points = [];

        for ($i = 0; $i < self::MAX_POINTS; $i++) {
            $points[] = $geometry[(int) round($i * $step)];
        }

        return $points;
    }
}
