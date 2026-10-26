<?php

namespace App\Services\Navigation;

use App\Models\Anchorage;
use App\Models\PlaceCoordinate;
use App\Models\Port;
use Illuminate\Support\Facades\Schema;

class PortCoordinates
{
    public function __construct(private readonly PortGeocoder $geocoder) {}

    /** @var array<string, array{name: string, latitude: float, longitude: float}> */
    private const LOCATIONS = [
        'saint-malo' => ['name' => 'Saint-Malo', 'latitude' => 48.649, 'longitude' => -2.025],
        'saint-quay-portrieux' => ['name' => 'Saint-Quay-Portrieux', 'latitude' => 48.648, 'longitude' => -2.825],
        'trebeurden' => ['name' => 'Trébeurden', 'latitude' => 48.769, 'longitude' => -3.567],
        'aber-wrach' => ['name' => 'Aber-Wrac’h', 'latitude' => 48.604, 'longitude' => -4.573],
        'camaret-sur-mer' => ['name' => 'Camaret-sur-Mer', 'latitude' => 48.276, 'longitude' => -4.596],
        'audierne' => ['name' => 'Audierne', 'latitude' => 48.025, 'longitude' => -4.541],
        'concarneau' => ['name' => 'Concarneau', 'latitude' => 47.875, 'longitude' => -3.921],
        'vannes' => ['name' => 'Vannes', 'latitude' => 47.655, 'longitude' => -2.760],
        'biarritz' => ['name' => 'Biarritz', 'latitude' => 43.483, 'longitude' => -1.558],
        'bonifacio' => ['name' => 'Bonifacio', 'latitude' => 41.388, 'longitude' => 9.159],
        'brest' => ['name' => 'Brest', 'latitude' => 48.390, 'longitude' => -4.486],
        'lorient' => ['name' => 'Lorient', 'latitude' => 47.748, 'longitude' => -3.370],
        'la-rochelle' => ['name' => 'La Rochelle', 'latitude' => 46.157, 'longitude' => -1.153],
        'marseille' => ['name' => 'Marseille', 'latitude' => 43.296, 'longitude' => 5.370],
        'quebec' => ['name' => 'Québec', 'latitude' => 46.813, 'longitude' => -71.208],
        'horta' => ['name' => 'Horta', 'latitude' => 38.537, 'longitude' => -28.627],
        'halifax' => ['name' => 'Halifax', 'latitude' => 44.648, 'longitude' => -63.575],
    ];

    /** @return array{name: string, latitude: float, longitude: float}|null */
    public function find(string $name, ?string $reference = null, bool $allowGeocoding = true): ?array
    {
        if ($reference !== null) {
            $place = $this->referenced($reference);

            return $place === null ? null : ['name' => $place->name, 'latitude' => $place->latitude, 'longitude' => $place->longitude];
        }
        $normalized = $this->normalize($name);
        if (isset(self::LOCATIONS[$normalized])) {
            return self::LOCATIONS[$normalized];
        }

        // A national import can contain a minor homonym of a city abroad.
        // An explicit reference wins; otherwise consult the global provider first.
        $global = $allowGeocoding ? $this->geocoder->locate($name) : null;
        if ($global !== null) {
            return $global;
        }

        $place = Schema::hasTable('place_coordinates')
            ? PlaceCoordinate::query()
                ->where('normalized_name', $normalized)
                ->orderBy('feature_class')
                ->first()
            : null;

        return $place === null ? null : [
            'name' => $place->name,
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ];
    }

    /** @return array<int, array{name: string, latitude: float, longitude: float}> */
    public function all(): array
    {
        return array_values(self::LOCATIONS);
    }

    public function referenced(string $reference): Port|Anchorage|PlaceCoordinate|null
    {
        if (preg_match('/^gps:(-?[0-9]+(?:\.[0-9]+)?),(-?[0-9]+(?:\.[0-9]+)?)$/', $reference, $point)) {
            $latitude = (float) $point[1];
            $longitude = (float) $point[2];
            if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
                return null;
            }

            return new PlaceCoordinate([
                'name' => sprintf('GPS %.5F, %.5F', $latitude == 0 ? 0 : $latitude, $longitude == 0 ? 0 : $longitude),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        if (! preg_match('/^(port|anchorage|locality):([1-9][0-9]*)$/', $reference, $matches)) {
            return null;
        }

        return match ($matches[1]) {
            'port' => Port::find($matches[2]),
            'anchorage' => Anchorage::find($matches[2]),
            'locality' => PlaceCoordinate::find($matches[2]),
        };
    }

    private function normalize(string $name): string
    {
        return str($name)->ascii()->lower()->replace(['’', "'"], '')->replace(' ', '-')->trim()->toString();
    }
}
