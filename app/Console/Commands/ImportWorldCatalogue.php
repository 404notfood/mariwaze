<?php

namespace App\Console\Commands;

use App\Services\Import\WorldCatalogueImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportWorldCatalogue extends Command
{
    protected $signature = 'maritime:import-world {--download : Télécharger les sources officielles} {--cities= : Fichier cities500.txt GeoNames} {--ports= : Fichier JSON World Port Index} {--admin1= : Fichier admin1CodesASCII.txt GeoNames}';

    protected $description = 'Importe les villes et ports du monde sans supprimer les données ni changer les références existantes.';

    public function handle(WorldCatalogueImporter $importer): int
    {
        $directory = storage_path('app/geodata');
        $cities = $this->input()->string('cities')->toString() ?: $directory.'/cities500.txt';
        $ports = $this->input()->string('ports')->toString() ?: $directory.'/world-ports.json';
        $admin = $this->input()->string('admin1')->toString() ?: $directory.'/admin1CodesASCII.txt';
        try {
            if ($this->option('download')) {
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                $this->info('Téléchargement GeoNames et NGA World Port Index…');
                foreach ([
                    $directory.'/cities500.zip' => 'https://download.geonames.org/export/dump/cities500.zip',
                    $admin => 'https://download.geonames.org/export/dump/admin1CodesASCII.txt',
                    $ports => 'https://msi.nga.mil/api/publications/world-port-index?output=json',
                ] as $path => $url) {
                    Http::timeout(60)->withOptions(['sink' => $path])->get($url)->throw();
                }
                if (! copy('phar://'.$directory.'/cities500.zip/cities500.txt', $cities)) {
                    $this->error('Impossible de lire cities500.txt dans l’archive.');

                    return self::FAILURE;
                }
            }
            if (! is_file($cities) || ! is_file($ports)) {
                $this->error('Sources absentes : utilisez --download ou fournissez --cities et --ports.');

                return self::FAILURE;
            }
            $this->info('Import du catalogue mondial…');
            $stats = DB::transaction(fn (): array => ['cities' => $importer->cities($cities, $admin), 'ports' => $importer->ports($ports)]);
            $this->info($stats['cities'].' localités et '.$stats['ports'].' ports mondiaux importés.');
        } catch (Throwable $exception) {
            $this->error('Import interrompu : '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
