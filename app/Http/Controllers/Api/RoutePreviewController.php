<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Navigation\InlandRouteService;
use App\Services\Navigation\SeaRouteService;
use App\Services\Routing\StopFinder;
use App\Services\Safety\RouteHazardService;
use App\Support\GeoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Calcule un trajet à la volée entre deux points cliqués sur la carte de
 * préparation, sans créer de croisière.
 */
class RoutePreviewController extends Controller
{
    public function __invoke(
        Request $request,
        SeaRouteService $sea,
        InlandRouteService $inland,
        RouteHazardService $hazards,
        StopFinder $stops,
    ): JsonResponse {
        $data = $request->validate([
            'from_latitude' => ['required', 'numeric', 'between:-90,90'],
            'from_longitude' => ['required', 'numeric', 'between:-180,180'],
            'to_latitude' => ['required', 'numeric', 'between:-90,90'],
            'to_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $origin = ['latitude' => (float) $data['from_latitude'], 'longitude' => (float) $data['from_longitude']];
        $destination = ['latitude' => (float) $data['to_latitude'], 'longitude' => (float) $data['to_longitude']];

        try {
            $geometry = $sea->route($origin, $destination);
        } catch (RuntimeException) {
            $geometry = $inland->route($origin, $destination)['geometry'] ?? [];
        }

        $geometry = array_values($geometry);

        if (count($geometry) < 2) {
            return response()->json([
                'message' => "Aucun trajet navigable trouvé entre ces deux points : vérifiez qu'ils sont en mer ou près d'une voie navigable importée.",
            ], 422);
        }

        $distance = 0.0;

        for ($i = 1; $i < count($geometry); $i++) {
            $distance += GeoScope::nauticalMiles(
                $geometry[$i - 1]['latitude'],
                $geometry[$i - 1]['longitude'],
                $geometry[$i]['latitude'],
                $geometry[$i]['longitude'],
            );
        }

        return response()->json([
            'geometry' => $geometry,
            'distance_nm' => (int) round($distance),
            'hazards' => $hazards->alertsForGeometry($geometry),
            'departure_port' => $stops->portsNear($origin['latitude'], $origin['longitude'], 15, 1)->first()?->name,
            'arrival_port' => $stops->portsNear($destination['latitude'], $destination['longitude'], 15, 1)->first()?->name,
        ]);
    }
}
