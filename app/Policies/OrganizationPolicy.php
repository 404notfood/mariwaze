<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user) !== null;
    }

    public function manageFleet(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user)?->canManage() ?? false;
    }
}
