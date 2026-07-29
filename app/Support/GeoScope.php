<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Recherche par proximité, partagée par les tables portant latitude/longitude.
 *
 * Trois stratégies selon les capacités du moteur :
 * - PostGIS : ST_DWithin sur la colonne geography (index GIST) ;
 * - SQL trigonométrique : boîte englobante puis haversine en SQL ;
 * - repli : boîte englobante seule, la distance exacte étant filtrée en PHP.
 *
 * SQLite est souvent compilé sans SQLITE_ENABLE_MATH_FUNCTIONS : ni acos() ni
 * radians() n'y sont disponibles, d'où le repli.
 */
class GeoScope
{
    private const EARTH_RADIUS_NM = 3440.065;

    private static ?bool $sqlTrigonometry = null;

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function near(Builder $query, float $latitude, float $longitude, float $nauticalMiles): Builder
    {
        if (Postgis::enabled()) {
            return $query
                ->whereNotNull('location')
                ->whereRaw(
                    'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                    [$longitude, $latitude, $nauticalMiles * 1852]
                )
                ->orderByRaw(
                    'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)',
                    [$longitude, $latitude]
                );
        }

        $latitudeDelta = $nauticalMiles / 60;
        $longitudeDelta = $nauticalMiles / max(0.01, 60 * cos(deg2rad($latitude)));

        $query
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta]);

        if (! self::supportsSqlTrigonometry($query)) {
            // La boîte englobante est un sur-ensemble du disque recherché : le
            // tri et le filtre exacts sont appliqués sur la collection.
            return $query;
        }

        $distance = sprintf(
            '(%F * acos(least(1, greatest(-1, '.
            'cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) '.
            '+ sin(radians(?)) * sin(radians(latitude))))))',
            self::EARTH_RADIUS_NM,
        );

        return $query
            ->whereRaw("{$distance} <= ?", [$latitude, $longitude, $latitude, $nauticalMiles])
            ->orderByRaw($distance, [$latitude, $longitude, $latitude]);
    }

    /**
     * Le moteur sait-il calculer une distance haversine en SQL ?
     *
     * @param  Builder<covariant Model>  $query
     */
    private static function supportsSqlTrigonometry(Builder $query): bool
    {
        if (self::$sqlTrigonometry !== null) {
            return self::$sqlTrigonometry;
        }

        $connection = $query->getConnection();

        if (! $connection instanceof Connection || $connection->getDriverName() !== 'sqlite') {
            return self::$sqlTrigonometry = true;
        }

        try {
            $connection->selectOne('select acos(least(1, greatest(-1, cos(radians(0))))) as probe');

            return self::$sqlTrigonometry = true;
        } catch (\Throwable) {
            return self::$sqlTrigonometry = false;
        }
    }

    /** Réinitialise la détection (utile en test). */
    public static function forget(): void
    {
        self::$sqlTrigonometry = null;
    }

    /** Distance orthodromique en milles nautiques. */
    public static function nauticalMiles(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $latitude = deg2rad($toLat - $fromLat);
        $longitude = deg2rad($toLon - $fromLon);
        $a = sin($latitude / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($longitude / 2) ** 2;

        return self::EARTH_RADIUS_NM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
