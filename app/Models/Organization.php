<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'type',
        'owner_id',
        'personal_owner_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Boat, $this> */
    public function boats(): HasMany
    {
        return $this->hasMany(Boat::class);
    }

    /** @return HasMany<Cruise, $this> */
    public function cruises(): HasMany
    {
        return $this->hasMany(Cruise::class);
    }

    public function roleFor(User $user): ?OrganizationRole
    {
        if ($this->owner_id === $user->id) {
            return OrganizationRole::Owner;
        }

        $role = DB::table('organization_user')
            ->where('organization_id', $this->id)
            ->where('user_id', $user->id)
            ->value('role');

        return is_string($role) ? OrganizationRole::tryFrom($role) : null;
    }
}
