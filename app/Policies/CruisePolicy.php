<?php

namespace App\Policies;

use App\Models\Cruise;
use App\Models\User;

class CruisePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cruise $cruise): bool
    {
        return $this->belongsToAccount($user, $cruise);
    }

    public function update(User $user, Cruise $cruise): bool
    {
        return $this->canManageAccount($user, $cruise);
    }

    public function delete(User $user, Cruise $cruise): bool
    {
        return $this->canManageAccount($user, $cruise);
    }

    private function belongsToAccount(User $user, Cruise $cruise): bool
    {
        return $user->organizations()->whereKey($cruise->organization_id)->exists();
    }

    private function canManageAccount(User $user, Cruise $cruise): bool
    {
        $organization = $cruise->organization()->first();

        return $organization?->roleFor($user)?->canManage() ?? false;
    }
}
