<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cruise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'boat_id',
        'slug',
        'title',
        'departure_port',
        'destination_port',
        'departure_date',
        'duration_days',
        'crew_count',
        'navigation_style',
        'navigation_area',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
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

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }
}
