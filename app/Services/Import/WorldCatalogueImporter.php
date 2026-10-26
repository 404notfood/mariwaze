<?php

namespace App\Services\Import;

use App\Models\PlaceCoordinate;
use App\Models\Port;
use App\Support\Postgis;
use RuntimeException;

class WorldCatalogueImporter
{
    public function cities(string $path, ?string $adminPath = null): int
    {
        $regions = [];
        if ($adminPath !== null && is_file($adminPath)) {
            foreach (file($adminPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $columns = explode("\t", $line);
                if (count($columns) >= 2) {
                    $regions[$columns[0]] = $columns[1];
                }
            }
        }
        $file = fopen($path, 'r');
        if ($file === false) {
            throw new RuntimeException('Fichier GeoNames illisible.');
        }
        $count = 0;
        $batch = [];
        try {
            while (($line = fgets($file)) !== false) {
                $row = explode("\t", rtrim($line, "\r\n"));
                if (count($row) < 19 || $row[6] !== 'P' || ! ctype_digit($row[0]) || ! $this->validPoint($row[4], $row[5]) || ! preg_match('/^[A-Z]{2}$/', $row[8])) {
                    continue;
                }
                $names = array_values(array_unique(array_filter(array_map(PlaceCoordinate::normalizeName(...), [$row[1], $row[2], ...explode(',', $row[3])]))));
                $batch[] = [
                    'geoname_id' => (int) $row[0], 'name' => $row[1], 'normalized_name' => PlaceCoordinate::normalizeName($row[1]),
                    'latitude' => (float) $row[4], 'longitude' => (float) $row[5], 'country_code' => $row[8],
                    'feature_class' => $row[6], 'feature_code' => $row[7], 'population' => max(0, (int) $row[14]),
                    'region' => $regions[$row[8].'.'.$row[10]] ?? null,
                    'alternate_names' => json_encode($names, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $count++;
                if (count($batch) >= 500) {
                    $this->saveCities($batch);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $this->saveCities($batch);
            }
        } finally {
            fclose($file);
        }

        return $count;
    }

    public function ports(string $path): int
    {
        $data = json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data['ports'] ?? null) || $data['ports'] === []) {
            throw new RuntimeException('Catalogue World Port Index vide ou invalide.');
        }
        $rows = [];
        foreach ($data['ports'] as $port) {
            if (! is_array($port) || ! is_numeric($port['portNumber'] ?? null) || ! is_string($port['portName'] ?? null)
                || ! $this->validPoint($port['ycoord'] ?? null, $port['xcoord'] ?? null)) {
                continue;
            }
            $rows[] = [
                'source' => 'nga-wpi', 'source_ref' => (string) $port['portNumber'],
                'slug' => str($port['portName'])->slug()->limit(180, '').'-wpi-'.$port['portNumber'],
                'name' => $port['portName'], 'region' => $port['regionName'] ?? $port['countryName'] ?? null,
                'country_code' => $port['countryCode'] ?? null,
                'latitude' => round((float) $port['ycoord'], 7), 'longitude' => round((float) $port['xcoord'], 7),
                'has_water' => ($port['suWater'] ?? null) === 'Y',
                'has_fuel' => ($port['suFuel'] ?? null) === 'Y' || ($port['suDiesel'] ?? null) === 'Y',
                'has_provisions' => ($port['suProvisions'] ?? null) === 'Y',
                'description' => 'Repère portuaire du NGA World Port Index. Accueil des plaisanciers, services, accès et profondeurs à confirmer auprès du port ; coordonnées de référence, pas une position de poste d’amarrage.',
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 300) as $batch) {
            Port::upsert($batch, ['source', 'source_ref'], ['name', 'region', 'country_code', 'latitude', 'longitude', 'has_water', 'has_fuel', 'has_provisions', 'description', 'updated_at']);
        }
        Postgis::syncPointColumn('ports');

        return count($rows);
    }

    /** @param list<array<string, mixed>> $batch */
    private function saveCities(array $batch): void
    {
        PlaceCoordinate::upsert($batch, ['geoname_id'], ['name', 'normalized_name', 'latitude', 'longitude', 'country_code', 'feature_class', 'feature_code', 'population', 'region', 'alternate_names', 'updated_at']);
    }

    private function validPoint(mixed $latitude, mixed $longitude): bool
    {
        return is_numeric($latitude) && is_numeric($longitude) && is_finite((float) $latitude) && is_finite((float) $longitude)
            && abs((float) $latitude) <= 90 && abs((float) $longitude) <= 180;
    }
}
