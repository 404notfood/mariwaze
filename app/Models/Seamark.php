<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Balise de navigation issue d'OpenSeaMap/OSM : bouées cardinales et
 * latérales, marques de danger isolé, eaux saines, phares et feux.
 */
class Seamark extends Model
{
    /** Libellés français par catégorie normalisée. */
    public const CATEGORIES = [
        'cardinal_north' => 'Cardinale nord',
        'cardinal_south' => 'Cardinale sud',
        'cardinal_east' => 'Cardinale est',
        'cardinal_west' => 'Cardinale ouest',
        'lateral_port' => 'Latérale bâbord',
        'lateral_starboard' => 'Latérale tribord',
        'isolated_danger' => 'Danger isolé',
        'safe_water' => 'Eaux saines',
        'special_purpose' => 'Marque spéciale',
        'lighthouse' => 'Phare',
        'light' => 'Feu',
    ];

    protected $fillable = [
        'source_ref',
        'name',
        'category',
        'seamark_type',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Balises dans une emprise géographique (requête d'affichage carte).
     *
     * @param  Builder<Seamark>  $query
     * @return Builder<Seamark>
     */
    public function scopeInBounds(Builder $query, float $south, float $west, float $north, float $east): Builder
    {
        return $query->whereBetween('latitude', [$south, $north])
            ->where(fn ($bounds) => $west <= $east
                ? $bounds->whereBetween('longitude', [$west, $east])
                : $bounds->where('longitude', '>=', $west)->orWhere('longitude', '<=', $east));
    }
}
