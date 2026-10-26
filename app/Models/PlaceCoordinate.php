<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlaceCoordinate extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'latitude',
        'longitude',
        'feature_class',
        'feature_code',
        'country_code',
        'region',
        'geoname_id',
        'population',
        'alternate_names',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'population' => 'integer',
            'alternate_names' => 'array',
        ];
    }

    public static function normalizeName(string $name): string
    {
        $value = str($name)->ascii()->lower()->replace(['’', "'"], '')->squish()->replace(' ', '-')->trim()->toString();

        return $value !== '' ? $value : str($name)->lower()->squish()->replace(' ', '-')->toString();
    }

    /** @param Builder<PlaceCoordinate> $query
     * @return Builder<PlaceCoordinate>
     */
    public function scopeNamed(Builder $query, string $name, bool $partial = false): Builder
    {
        $normalized = self::normalizeName($name);

        return $query->where(fn ($matches) => $matches
            ->where('normalized_name', $partial ? 'like' : '=', $partial ? '%'.$normalized.'%' : $normalized)
            ->orWhereJsonContains('alternate_names', $normalized));
    }
}
