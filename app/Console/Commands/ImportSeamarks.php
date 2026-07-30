<?php

namespace App\Console\Commands;

use App\Services\Import\MaritimePlaceImporter;
use App\Services\Import\SeamarkImporter;
use Illuminate\Console\Command;

class ImportSeamarks extends Command
{
    protected $signature = 'seamarks:import
        {--area=* : Façades à importer (voir MaritimePlaceImporter::SEA_AREAS), toutes par défaut}
        {--replace : Vide le balisage existant avant import}';

    protected $description = 'Importe le balisage OpenSeaMap (cardinales, latérales, phares, feux) via Overpass';

    public function handle(SeamarkImporter $importer): int
    {
        $areas = MaritimePlaceImporter::SEA_AREAS;
        $requested = (array) $this->option('area');

        if ($requested !== []) {
            $areas = array_intersect_key($areas, array_flip($requested));

            if ($areas === []) {
                $this->error('Façades inconnues. Disponibles : '.implode(', ', array_keys(MaritimePlaceImporter::SEA_AREAS)));

                return self::FAILURE;
            }
        }

        $replace = (bool) $this->option('replace');
        $totals = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($areas as $name => $bbox) {
            $this->info("Façade {$name}…");

            $stats = $importer->importArea($bbox, $replace, 2.0, function (array $tile, int $imported): void {
                $this->line(sprintf('  tuile %.1f,%.1f : %d balises', $tile['south'], $tile['west'], $imported));
            });

            $replace = false; // Une seule purge, avant la première façade.
            $totals['imported'] += $stats['imported'];
            $totals['failed'] += $stats['failed'];
        }

        $this->info("Import terminé : {$totals['imported']} balises ({$totals['failed']} tuiles en échec).");

        return self::SUCCESS;
    }
}
