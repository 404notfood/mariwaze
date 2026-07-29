<?php

namespace App\Services\Routing;

use App\Models\Anchorage;
use App\Models\Boat;
use App\Models\Port;
use App\Support\GeoScope;
use Illuminate\Support\Collection;

/**
 * Recherche des escales réelles (ports et mouillages) autour d'un point ou
 * le long d'une route. Remplace les fournisseurs de données mock.
 */
class StopFinder
{
    public function __construct(private readonly BoatCompatibilityService $compatibility) {}

    /**
     * Ports proches, triés par distance croissante.
     *
     * @return Collection<int, Port>
     */
    public function portsNear(float $latitude, float $longitude, float $radiusNm = 25, int $limit = 10): Collection
    {
        $ports = Port::query()->near($latitude, $longitude, $radiusNm)->get();

        foreach ($ports as $port) {
            $port->distance_nm = GeoScope::nauticalMiles($latitude, $longitude, $port->latitude, $port->longitude);
        }

        return $ports
            ->filter(fn (Port $port): bool => ($port->distance_nm ?? INF) <= $radiusNm)
            ->sortBy('distance_nm')
            ->take($limit)
            ->values();
    }

    /**
     * Mouillages proches, triés par distance croissante.
     *
     * @return Collection<int, Anchorage>
     */
    public function anchoragesNear(float $latitude, float $longitude, float $radiusNm = 25, int $limit = 10): Collection
    {
        $anchorages = Anchorage::query()->near($latitude, $longitude, $radiusNm)->get();

        foreach ($anchorages as $anchorage) {
            $anchorage->distance_nm = GeoScope::nauticalMiles(
                $latitude,
                $longitude,
                $anchorage->latitude,
                $anchorage->longitude,
            );
        }

        return $anchorages
            ->filter(fn (Anchorage $anchorage): bool => ($anchorage->distance_nm ?? INF) <= $radiusNm)
            ->sortBy('distance_nm')
            ->take($limit)
            ->values();
    }

    /**
     * Escales compatibles avec un bateau donné, enrichies du score de compatibilité.
     *
     * @return array<int, array{model: Anchorage, compatible: bool, compatibility_score: int, reasons: list<string>}>
     */
    public function compatibleAnchorages(Boat $boat, float $latitude, float $longitude, float $radiusNm = 25, int $limit = 10): array
    {
        return $this->anchoragesNear($latitude, $longitude, $radiusNm, $limit * 2)
            ->map(function (Anchorage $anchorage) use ($boat): array {
                $check = $this->compatibility->forAnchorage($boat, $anchorage);

                return [
                    'model' => $anchorage,
                    'compatible' => $check['compatible'],
                    'compatibility_score' => $check['score'],
                    'reasons' => $check['reasons'],
                ];
            })
            ->sortByDesc('compatibility_score')
            ->take($limit)
            ->values()
            ->all();
    }
}
