<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * L'auteur est nullable : les avis survivent à la suppression du compte.
 *
 * @property-read Carbon|null $visited_on
 * @property-read User|null $user
 */
class Review extends Model
{
    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'body',
        'visited_on',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'visited_on' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
