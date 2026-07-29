<?php

namespace App\Console\Commands;

use App\Services\Import\MaritimePlaceImporter;
use App\Services\Import\OverpassUnavailable;
use Illuminate\Console\Command;

class ImportMaritimePlaces extends Command
{
    protected $signature = 'maritime:import-places
        {--area=* : Facade maritime predefinie (toutes par defaut)}
        {--south= : Latitude sud d une zone personnalisee}
        {--west= : Longitude ouest d une zone personnalisee}
        {--north= : Latitude nord d une zone personnalisee}
        {--east= : Longitude est d une zone personnalisee}
        {--tile=1.0 : Taille des tuiles en degres (Overpass limite les grandes zones)}
        {--replace : Supprime les places OSM existantes avant import}';

    protected $description = 'Importe les ports et mouillages reels depuis OpenStreetMap via Overpass.';

    public function handle(MaritimePlaceImporter $importer): int
    {
        $areas = $this->resolveAreas();

        if ($areas === null) {
            return self::FAILURE;
        }

        $tileSize = max(0.1, (float) $this->option('tile'));
        $totals = ['ports' => 0, 'anchorages' => 0, 'skipped' => 0, 'failed' => 0];
        $replace = (bool) $this->option('replace');

        foreach ($areas as $name => $bbox) {
            $tiles = $importer->tiles($bbox, $tileSize);
            $this->info(sprintf('%s : %d tuile(s)', $name, count($tiles)));

            $bar = $this->output->createProgressBar(count($tiles));
            $bar->start();

            try {
                $stats = $importer->importArea($bbox, $replace, $tileSize, function () use ($bar): void {
                    $bar->advance();
                });
            } catch (OverpassUnavailable $e) {
                $bar->finish();
                $this->newLine();
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $bar->finish();
            $this->newLine();

            // Le --replace ne doit vider les tables que sur la premiere facade.
            $replace = false;

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $stats[$key];
            }
        }

        $this->newLine();
        $this->table(
            ['Ports', 'Mouillages', 'Ignores', 'Tuiles en echec'],
            [[$totals['ports'], $totals['anchorages'], $totals['skipped'], $totals['failed']]],
        );

        if ($totals['failed'] > 0) {
            $this->warn(sprintf(
                '%d tuile(s) ont echoue (Overpass surcharge). Relancez la commande pour completer.',
                $totals['failed'],
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{south: float, west: float, north: float, east: float}>|null
     */
    private function resolveAreas(): ?array
    {
        $custom = ['south', 'west', 'north', 'east'];
        $provided = array_filter($custom, fn (string $key): bool => $this->option($key) !== null);

        if ($provided !== []) {
            if (count($provided) !== 4) {
                $this->error('Une zone personnalisee exige --south, --west, --north et --east.');

                return null;
            }

            $bbox = [
                'south' => (float) $this->option('south'),
                'west' => (float) $this->option('west'),
                'north' => (float) $this->option('north'),
                'east' => (float) $this->option('east'),
            ];

            if ($bbox['south'] >= $bbox['north'] || $bbox['west'] >= $bbox['east']) {
                $this->error('Bounding box invalide : sud/ouest doivent etre inferieurs a nord/est.');

                return null;
            }

            return ['zone personnalisee' => $bbox];
        }

        $requested = (array) $this->option('area');

        if ($requested === []) {
            return MaritimePlaceImporter::SEA_AREAS;
        }

        $areas = [];

        foreach ($requested as $name) {
            if (! isset(MaritimePlaceImporter::SEA_AREAS[$name])) {
                $this->error(sprintf(
                    'Facade inconnue : %s. Disponibles : %s',
                    $name,
                    implode(', ', array_keys(MaritimePlaceImporter::SEA_AREAS)),
                ));

                return null;
            }

            $areas[$name] = MaritimePlaceImporter::SEA_AREAS[$name];
        }

        return $areas;
    }
}
