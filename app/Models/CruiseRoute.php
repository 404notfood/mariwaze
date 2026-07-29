<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read array<string, mixed>|null $score_breakdown
 * @property-read list<array{latitude: float, longitude: float}>|null $geometry
 * @property-read Carbon|null $computed_at
 */
class CruiseRoute extends Model
{
    protected $fillable = [
        'cruise_id',
        'scenario',
        'label',
        'is_selected',
        'distance_nm',
        'duration_days',
        'stops',
        'score',
        'score_breakdown',
        'geometry',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_selected' => 'boolean',
            'distance_nm' => 'integer',
            'duration_days' => 'integer',
            'stops' => 'integer',
            'score' => 'integer',
            'score_breakdown' => 'array',
            'geometry' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Cruise, $this> */
    public function cruise(): BelongsTo
    {
        return $this->belongsTo(Cruise::class);
    }

    /** @return HasMany<RouteSegment, $this> */
    public function segments(): HasMany
    {
        return $this->hasMany(RouteSegment::class)->orderBy('position');
    }
}
