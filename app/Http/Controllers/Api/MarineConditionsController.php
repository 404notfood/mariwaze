<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MarineConditionsUnavailable;
use App\Http\Controllers\Controller;
use App\Services\Marine\OpenMeteoMarineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarineConditionsController extends Controller
{
    public function __invoke(Request $request, OpenMeteoMarineService $marine): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            return response()->json($marine->conditions(
                (float) $data['latitude'],
                (float) $data['longitude'],
            ));
        } catch (MarineConditionsUnavailable) {
            return response()->json([
                'message' => 'Les données marines sont temporairement indisponibles. Réessayez dans quelques minutes.',
            ], 503);
        }
    }
}
