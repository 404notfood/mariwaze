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
class Anchorage extends Model
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
        'bottom_type',
        'holding',
        'protected_from',
        'exposure',
        'tags',
        'warnings',
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
            'protected_from' => 'array',
            'tags' => 'array',
            'warnings' => 'array',
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
     * @param  Builder<Anchorage>  $query
     * @return Builder<Anchorage>
     */
    public function scopeNear(Builder $query, float $latitude, float $longitude, float $nauticalMiles = 25): Builder
    {
        return GeoScope::near($query, $latitude, $longitude, $nauticalMiles);
    }

    public function depthLabel(): ?string
    {
        if ($this->min_depth_meters === null && $this->max_depth_meters === null) {
            return null;
        }

        if ($this->min_depth_meters === null || $this->max_depth_meters === null) {
            return number_format((float) ($this->min_depth_meters ?? $this->max_depth_meters), 1, ',', ' ').' m';
        }

        return number_format($this->min_depth_meters, 1, ',', ' ').' – '.number_format($this->max_depth_meters, 1, ',', ' ').' m';
    }
}
