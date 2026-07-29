<?php

namespace App\Models;

use App\Support\GeoScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Agrégats ajoutés par withCount() / withAvg() sur les requêtes de fiche.
 *
 * @property-read int $reviews_count
 * @property-read int $photos_count
 * @property-read float|null $reviews_avg_rating
 */
class Port extends Model
{
    /** Distance au point de recherche, renseignée par StopFinder (non persistée). */
    public ?float $distance_nm = null;

    protected $fillable = [
        'slug',
        'name',
        'region',
        'country_code',
        'latitude',
        'longitude',
        'min_depth_meters',
        'max_depth_meters',
        'max_length_meters',
        'max_width_meters',
        'berths',
        'has_fuel',
        'has_water',
        'has_electricity',
        'has_shower',
        'has_repair',
        'has_provisions',
        'vhf_channel',
        'phone',
        'website',
        'night_price_eur',
        'description',
        'source',
        'source_ref',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'min_depth_meters' => 'float',
            'max_depth_meters' => 'float',
            'max_length_meters' => 'float',
            'max_width_meters' => 'float',
            'berths' => 'integer',
            'has_fuel' => 'boolean',
            'has_water' => 'boolean',
            'has_electricity' => 'boolean',
            'has_shower' => 'boolean',
            'has_repair' => 'boolean',
            'has_provisions' => 'boolean',
            'night_price_eur' => 'float',
        ];
    }

    /** @return MorphMany<Review, $this> */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /** @return MorphMany<Photo, $this> */
    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    /**
     * Ports situés dans un rayon donné (milles nautiques) autour d'un point.
     * Utilise PostGIS quand l'extension est active, sinon une boîte lat/lon
     * suivie du filtre haversine appliqué côté requête.
     */
    /**
     * @param  Builder<Port>  $query
     * @return Builder<Port>
     */
    public function scopeNear(Builder $query, float $latitude, float $longitude, float $nauticalMiles = 25): Builder
    {
        return GeoScope::near($query, $latitude, $longitude, $nauticalMiles);
    }

    /** @return array<int, string> */
    public function services(): array
    {
        return collect([
            'Carburant' => $this->has_fuel,
            'Eau' => $this->has_water,
            'Électricité' => $this->has_electricity,
            'Douches' => $this->has_shower,
            'Réparation' => $this->has_repair,
            'Avitaillement' => $this->has_provisions,
        ])->filter()->keys()->all();
    }
}
