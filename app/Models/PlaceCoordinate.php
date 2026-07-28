<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
