<?php

namespace App\Services\Import;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Accès à l'API Overpass (OpenStreetMap).
 *
 * L'API est publique et sujette à limitation : les requêtes sont volontairement
 * bornées par une bounding box et un timeout explicite.
 */
class OverpassClient
{
    /**
     * @return array<int, array<string, mixed>> Éléments OSM bruts
     *
     * @throws OverpassUnavailable
     */
    public function query(string $overpassQl): array
    {
        try {
            $response = Http::asForm()
                ->withUserAgent(config('services.overpass.user_agent'))
                ->timeout((int) config('services.overpass.timeout'))
                ->post(config('services.overpass.url'), ['data' => $overpassQl])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new OverpassUnavailable('Overpass est injoignable : '.$e->getMessage(), previous: $e);
        }

        $elements = $response->json('elements');

        return is_array($elements) ? $elements : [];
    }

    /**
     * Construit une requête Overpass pour des tags donnés dans une bounding box.
     *
     * @param  list<string>  $filters  Filtres OverpassQL, ex. 'node["leisure"="marina"]'
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     */
    public function build(array $filters, array $bbox, int $timeout = 120): string
    {
        $box = sprintf('%F,%F,%F,%F', $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east']);
        $body = collect($filters)
            ->map(fn (string $filter): string => "  {$filter}({$box});")
            ->implode("\n");

        return "[out:json][timeout:{$timeout}];\n(\n{$body}\n);\nout center tags;";
    }
}
