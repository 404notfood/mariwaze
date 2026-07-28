<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Boat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'model',
        'boat_type',
        'flag',
        'mmsi',
        'length',
        'width',
        'draft',
        'mast_height',
        'avg_speed',
        'fuel_range',
        'fleet_status',
        'berths',
        'fuel_consumption_per_hour',
    ];

    protected function casts(): array
    {
        return [
            'length' => 'float',
            'width' => 'float',
            'draft' => 'float',
            'mast_height' => 'float',
            'avg_speed' => 'float',
            'fuel_range' => 'integer',
            'berths' => 'integer',
            'fuel_consumption_per_hour' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function cruises(): HasMany
    {
        return $this->hasMany(Cruise::class);
    }
}
