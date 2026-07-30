<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seamark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Balisage affiché sur la carte quand le zoom est suffisant.
 */
class SeamarkController extends Controller
{
    private const MAX_SPAN_DEGREES = 2.0;
    private const MAX_RESULTS = 800;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'south' => ['required', 'numeric', 'between:-90,90'],
            'north' => ['required', 'numeric', 'between:-90,90', 'gte:south'],
            'west' => ['required', 'numeric', 'between:-180,180'],
            'east' => ['required', 'numeric', 'between:-180,180', 'gte:west'],
        ]);

        $south = (float) $data['south'];
        $north = (float) $data['north'];
        $west = (float) $data['west'];
        $east = (float) $data['east'];

        // Une emprise trop large signifie un zoom trop faible : rien à afficher.
        if ($north - $south > self::MAX_SPAN_DEGREES || $east - $west > self::MAX_SPAN_DEGREES) {
            return response()->json([]);
        }

        return response()->json(
            Seamark::query()
                ->inBounds($south, $west, $north, $east)
                ->limit(self::MAX_RESULTS)
                ->get()
                ->map(fn (Seamark $seamark): array => [
                    'name' => $seamark->name,
                    'category' => $seamark->category,
                    'label' => $seamark->categoryLabel(),
                    'latitude' => $seamark->latitude,
                    'longitude' => $seamark->longitude,
                ])
                ->all(),
        );
    }
}
