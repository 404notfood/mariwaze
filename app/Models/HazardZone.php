<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Zone de vigilance ou de danger pour la navigation (zone militaire,
 * interactions orques, piraterie, trafic dense, aire protégée).
 *
 * La zone est modélisée par un disque centre + rayon : les couloirs allongés
 * (côte portugaise pour les orques, par exemple) sont couverts par plusieurs
 * zones successives.
 *
 * @property-read list<int>|null $months
 * @property-read array<int, array{latitude: float, longitude: float}>|null $geometry
 */
class HazardZone extends Model
{
    public const CATEGORIES = [
        'military' => 'Zone militaire',
        'orca' => 'Interactions orques',
        'piracy' => 'Piraterie',
        'traffic' => 'Trafic dense',
        'nature' => 'Zone protégée',
    ];

    protected $fillable = [
        'slug',
        'name',
        'category',
        'severity',
        'latitude',
        'longitude',
        'radius_nm',
        'description',
        'advice',
        'months',
        'source',
        'geometry',
        'source_ref',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_nm' => 'float',
            'months' => 'array',
            'geometry' => 'array',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
