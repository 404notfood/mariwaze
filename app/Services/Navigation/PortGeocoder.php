<?php

namespace App\Services\Navigation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PortGeocoder
{
    /** @return array{name: string, latitude: float, longitude: float}|null */
    public function locate(string $query): ?array
    {
        $key = 'port-geocoder:'.sha1(str($query)->lower()->squish()->toString());

        if (filled(config('services.geocoding.mapbox_token')) && ! config('services.geocoding.mapbox_permanent')) {
            return $this->resolve($query);
        }

        return Cache::remember($key, now()->addDays(90), fn (): ?array => $this->resolve($query));
    }

    /** @return array{name: string, latitude: float, longitude: float}|null */
    private function resolve(string $query): ?array
    {
        try {
            $token = config('services.geocoding.mapbox_token');

            if (filled($token)) {
                return $this->fromMapbox($query, $token);
            }

            if ($this->canUseNominatim()) {
                return $this->fromNominatim($query);
            }
        } catch (ConnectionException|RequestException) {
            return null;
        }

        return null;
    }

    private function canUseNominatim(): bool
    {
        if (! app()->environment('production')) {
            return ! app()->environment('testing');
        }

        return parse_url(config('services.geocoding.nominatim_url'), PHP_URL_HOST) !== 'nominatim.openstreetmap.org';
    }

    /** @return array{name: string, latitude: float, longitude: float}|null */
    private function fromMapbox(string $query, string $token): ?array
    {
        $feature = Http::acceptJson()
            ->timeout(config('services.geocoding.timeout'))
            ->get(config('services.geocoding.mapbox_url'), [
                'q' => $query,
                'limit' => 1,
                'language' => 'fr',
                'permanent' => config('services.geocoding.mapbox_permanent'),
                'access_token' => $token,
            ])
            ->throw()
            ->json('features.0');

        $coordinates = $feature['geometry']['coordinates'] ?? null;
        if (! is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        return [
            'name' => $feature['properties']['name'] ?? $feature['name'] ?? $query,
            'longitude' => (float) $coordinates[0],
            'latitude' => (float) $coordinates[1],
        ];
    }

    /** @return array{name: string, latitude: float, longitude: float}|null */
    private function fromNominatim(string $query): ?array
    {
        $feature = Http::acceptJson()
            ->withUserAgent(config('services.geocoding.user_agent'))
            ->timeout(config('services.geocoding.timeout'))
            ->get(config('services.geocoding.nominatim_url'), [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
            ])
            ->throw()
            ->json('0');

        if (! is_array($feature) || ! isset($feature['lat'], $feature['lon'])) {
            return null;
        }

        return [
            'name' => $feature['display_name'] ?? $query,
            'latitude' => (float) $feature['lat'],
            'longitude' => (float) $feature['lon'],
        ];
    }
}
