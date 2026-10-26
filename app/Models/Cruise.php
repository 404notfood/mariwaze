<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $departure_date
 */
class Cruise extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'boat_id',
        'slug',
        'title',
        'departure_port',
        'destination_port',
        'departure_place',
        'destination_place',
        'departure_date',
        'duration_days',
        'crew_count',
        'navigation_style',
        'navigation_area',
        'status',
        'route_generation_status',
        'route_generation_token',
        'route_generation_error',
        'route_generation_started_at',
        'route_generation_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'route_generation_started_at' => 'immutable_datetime',
            'route_generation_completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Boat, $this> */
    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    /** @return HasMany<CruiseRoute, $this> */
    public function routes(): HasMany
    {
        return $this->hasMany(CruiseRoute::class);
    }

    /** @return HasOne<CruiseRoute, $this> */
    public function selectedRoute(): HasOne
    {
        return $this->hasOne(CruiseRoute::class)->where('is_selected', true);
    }
}
