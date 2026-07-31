<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read array<string, mixed>|null $weather_snapshot
 */
class RouteSegment extends Model
{
    protected $fillable = [
        'cruise_route_id',
        'position',
        'day',
        'from_name',
        'to_name',
        'from_latitude',
        'from_longitude',
        'to_latitude',
        'to_longitude',
        'port_id',
        'anchorage_id',
        'distance_nm',
        'duration_hours',
        'min_depth_meters',
        'overnight',
        'weather_score',
        'weather_snapshot',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'day' => 'integer',
            'from_latitude' => 'float',
            'from_longitude' => 'float',
            'to_latitude' => 'float',
            'to_longitude' => 'float',
            'distance_nm' => 'float',
            'duration_hours' => 'float',
            'min_depth_meters' => 'float',
            'weather_score' => 'integer',
            'weather_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<CruiseRoute, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(CruiseRoute::class, 'cruise_route_id');
    }

    /** @return BelongsTo<Port, $this> */
    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    /** @return BelongsTo<Anchorage, $this> */
    public function anchorage(): BelongsTo
    {
        return $this->belongsTo(Anchorage::class);
    }
}
