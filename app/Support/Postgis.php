<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Détection et utilisation de PostGIS.
 *
 * L'extension n'est pas garantie sur toutes les installations PostgreSQL, et les
 * tests tournent sur SQLite. Les tables portent donc toujours latitude/longitude
 * en decimal, et la colonne GEOGRAPHY n'est ajoutée que lorsque PostGIS répond.
 */
class Postgis
{
    private static ?bool $enabled = null;

    public static function enabled(): bool
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return self::$enabled = false;
        }

        try {
            return self::$enabled = DB::table('pg_extension')->where('extname', 'postgis')->exists();
        } catch (\Throwable) {
            return self::$enabled = false;
        }
    }

    /** Réinitialise le cache de détection (utile en test). */
    public static function forget(): void
    {
        self::$enabled = null;
    }

    /**
     * Ajoute une colonne GEOGRAPHY(Point, 4326) et son index GIST quand PostGIS
     * est actif. Sans PostGIS, les colonnes decimal restent la seule source.
     */
    public static function addPointColumn(string $table, string $column = 'location'): void
    {
        if (! self::enabled()) {
            return;
        }

        DB::statement("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS {$column} geography(Point, 4326)");
        DB::statement("CREATE INDEX IF NOT EXISTS {$table}_{$column}_gist ON {$table} USING GIST ({$column})");
    }

    /**
     * Synchronise la colonne géographique à partir de latitude/longitude.
     */
    public static function syncPointColumn(string $table, string $column = 'location'): void
    {
        if (! self::enabled()) {
            return;
        }

        DB::statement(
            "UPDATE {$table} SET {$column} = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography ".
            'WHERE longitude IS NOT NULL AND latitude IS NOT NULL'
        );
    }

    /**
     * Index géographique classique sur une table déjà pourvue de lat/lon.
     */
    public static function indexLatLon(Blueprint $table): void
    {
        $table->index(['latitude', 'longitude']);
    }
}
