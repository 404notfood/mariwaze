<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anchorage;
use App\Models\PlaceCoordinate;
use App\Models\Port;
use App\Services\Navigation\PortCoordinates;
use App\Services\Navigation\PortGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceSearchController extends Controller
{
    public function __invoke(Request $request, PortCoordinates $coordinates, PortGeocoder $geocoder): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'worldwide' => ['sometimes', 'boolean'],
        ]);
        // Remote geocoding is an explicit search, never an autocomplete request.
        if ($request->boolean('worldwide')) {
            $attribution = filled(config('services.geocoding.mapbox_token')) ? 'Mapbox' : 'OpenStreetMap';
            $place = $geocoder->search(trim($data['q']));
            if ($place === null) {
                return response()->json(['places' => [], 'attribution' => $attribution]);
            }

            $record = $geocoder->canPersist() ? PlaceCoordinate::updateOrCreate([
                'name' => $place['name'],
                'normalized_name' => $this->normalize($place['name']),
                'latitude' => round($place['latitude'], 7),
                'longitude' => round($place['longitude'], 7),
                'country_code' => $place['country_code'] ?? 'ZZ',
            ], ['feature_class' => 'P', 'feature_code' => 'PPL', 'region' => $place['region']]) : null;

            return response()->json(['places' => [[
                ...$place,
                'reference' => $record === null ? null : 'locality:'.$record->id,
                'type' => 'locality',
            ]], 'attribution' => $attribution]);
        }

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
            if (str_contains($this->normalize($place['name']), $this->normalize($search))) {
                $places[] = [...$place, 'reference' => null, 'region' => null, 'country_code' => null, 'type' => 'locality'];
            }
        }
        foreach (PlaceCoordinate::query()->where('normalized_name', 'like', '%'.$this->normalize($search).'%')->orderBy('name')->orderBy('id')->limit(8)->get() as $place) {
            $places[] = [
                'reference' => 'locality:'.$place->id,
                'name' => $place->name,
                'region' => $place->region,
                'country_code' => $place->country_code === 'ZZ' ? null : $place->country_code,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'type' => 'locality',
            ];
        }

        return response()->json(['places' => array_slice($places, 0, 20)]);
    }

    private function normalize(string $name): string
    {
        return str($name)->ascii()->lower()->replace(['’', "'"], '')->squish()->replace(' ', '-')->trim()->toString();
    }
}
