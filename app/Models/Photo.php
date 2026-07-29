<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * L'auteur est nullable : les photos survivent à la suppression du compte.
 *
 * @property-read User|null $user
 */
class Photo extends Model
{
    protected $fillable = [
        'user_id',
        'photoable_type',
        'photoable_id',
        'path',
        'caption',
        'latitude',
        'longitude',
        'taken_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'taken_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}
