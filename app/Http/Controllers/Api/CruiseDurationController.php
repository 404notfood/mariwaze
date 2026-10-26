<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Itinerary\CruiseDurationEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CruiseDurationController extends Controller
{
    public function __invoke(Request $request, CruiseDurationEstimator $estimator): JsonResponse
    {
        $data = $request->validate([
            'departure_port' => ['required', 'string', 'max:120'],
            'destination_port' => ['required', 'string', 'max:120'],
            'navigation_style' => ['required', 'in:Rapide,Confort,Découverte,Famille'],
            'departure_place' => ['nullable', 'string', 'max:80'],
            'destination_place' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json([
            'days' => $estimator->estimate($data['departure_port'], $data['destination_port'], $data['navigation_style'], $data['departure_place'] ?? null, $data['destination_place'] ?? null, allowGeocoding: false),
        ]);
    }
}
