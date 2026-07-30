<?php

namespace App\Console\Commands;

use App\Services\Import\HazardAreaImporter;
use Illuminate\Console\Command;

class ImportHazardAreas extends Command
{
    protected $signature = 'hazards:import-areas
        {--south=} {--west=} {--north=} {--east=}
        {--replace : Supprime les zones OSM existantes avant import}';

    protected $description = 'Importe les contours réels des DST et zones militaires (OpenSeaMap/OSM) via Overpass';

    public function handle(HazardAreaImporter $importer): int
    {
        $bbox = HazardAreaImporter::DEFAULT_BBOX;

        foreach (['south', 'west', 'north', 'east'] as $edge) {
            $value = $this->option($edge);

            if (is_numeric($value)) {
                $bbox[$edge] = (float) $value;
            }
        }

        $stats = $importer->importArea($bbox, (bool) $this->option('replace'), 4.0, function (array $tile, int $imported): void {
            $this->line(sprintf('  tuile %.1f,%.1f : %d zones', $tile['south'], $tile['west'], $imported));
        });

        $this->info("Import terminé : {$stats['imported']} zones ({$stats['failed']} tuiles en échec).");

        return self::SUCCESS;
    }
}
