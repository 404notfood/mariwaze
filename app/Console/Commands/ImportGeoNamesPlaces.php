<?php

namespace App\Console\Commands;

use App\Models\PlaceCoordinate;
use Illuminate\Console\Command;

class ImportGeoNamesPlaces extends Command
{
    protected $signature = 'geodata:import-geonames {path? : Fichier GeoNames TSV} {--country=FR : Code pays à remplacer}';

    protected $description = 'Importe les lieux GeoNames dans la base locale de coordonnées';

    public function handle(): int
    {
        $countryOption = $this->input()->string('country')->toString();
        $path = $this->input()->string('path')->toString() ?: storage_path('app/geodata/'.$countryOption.'.txt');
        $country = strtoupper($countryOption);

        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $file = fopen($path, 'r');
        if ($file === false) {
            $this->error("Impossible de lire : {$path}");

            return self::FAILURE;
        }

        PlaceCoordinate::query()->where('country_code', $country)->delete();
        $records = [];
        $imported = 0;

        while (($line = fgets($file)) !== false) {
            $columns = explode("\t", rtrim($line, "\r\n"));
            if (count($columns) < 9 || ! in_array($columns[6], ['P', 'S'], true)) {
                continue;
            }

            $records[] = [
                'name' => $columns[1],
                'normalized_name' => $this->normalize($columns[1]),
                'latitude' => (float) $columns[4],
                'longitude' => (float) $columns[5],
                'feature_class' => $columns[6],
                'feature_code' => $columns[7],
                'country_code' => $columns[8],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($records) === 1000) {
                PlaceCoordinate::query()->insert($records);
                $imported += count($records);
                $records = [];
            }
        }

        fclose($file);

        if ($records !== []) {
            PlaceCoordinate::query()->insert($records);
            $imported += count($records);
        }

        $this->info("{$imported} lieux importés pour {$country}.");

        return self::SUCCESS;
    }

    private function normalize(string $value): string
    {
        return str($value)->ascii()->lower()->replace(['’', "'"], '')->replace(' ', '-')->trim()->toString();
    }
}
