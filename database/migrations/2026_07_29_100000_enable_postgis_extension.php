<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostGIS est la « mémoire géographique » du produit (doc/schema-postgis.md).
     * L'extension n'est pas toujours compilée avec le serveur : on l'active quand
     * elle est disponible, et on laisse les colonnes decimal servir de repli sinon.
     */
    public function up(): void
    {
        if (! $this->isPostgres() || ! $this->isAvailable()) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        // L'extension est laissée en place : d'autres schémas de la base peuvent en dépendre.
    }

    private function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    private function isAvailable(): bool
    {
        return DB::table('pg_available_extensions')->where('name', 'postgis')->exists();
    }
};
