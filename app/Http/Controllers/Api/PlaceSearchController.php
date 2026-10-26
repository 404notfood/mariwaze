<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anchorage;
use App\Models\Port;
use App\Services\Navigation\PortCoordinates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceSearchController extends Controller
{
    public function __invoke(Request $request, PortCoordinates $coordinates): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:120']]);
        $search = mb_strtolower(trim($data['q']));
        $places = [];
        foreach (['port' => Port::class, 'anchorage' => Anchorage::class] as $type => $model) {
            foreach ($model::query()->whereRaw('lower(name) like ?', ['%'.$search.'%'])->orderBy('name')->orderBy('id')->limit(8)->get() as $place) {
                $places[] = [
                    'reference' => $type.':'.$place->id,
                    'name' => $place->name,
                    'region' => $place->region,
                    'country_code' => $place->country_code,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                    'type' => $type,
                ];
            }
        }
        foreach ($coordinates->all() as $place) {
            if (str_contains(mb_strtolower($place['name']), $search)) {
                $places[] = [...$place, 'reference' => null, 'region' => null, 'country_code' => null, 'type' => 'locality'];
            }
        }

        return response()->json(['places' => array_slice($places, 0, 20)]);
    }
}
