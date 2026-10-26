<?php

namespace App\Services\Accounts;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountContext
{
    public function current(?User $user): ?Organization
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasTable('organization_user')) {
            return null;
        }

        if ($user === null) {
            return null;
        }

        $organization = $user->organizations()->latest('organizations.created_at')->first();

        if ($organization !== null) {
            return $organization;
        }

        return DB::transaction(function () use ($user): Organization {
            $organization = Organization::query()->firstOrCreate(
                ['personal_owner_id' => $user->id],
                ['owner_id' => $user->id, 'type' => 'personal', 'name' => $user->name],
            );
            $organization->members()->syncWithoutDetaching([
                $user->id => ['role' => 'owner'],
            ]);

            return $organization;
        });
    }

    /** @throws AuthenticationException */
    public function currentOrCreate(?User $user): Organization
    {
        if ($user === null) {
            throw new AuthenticationException;
        }

        $organization = $this->current($user);

        if ($organization !== null) {
            return $organization;
        }

        throw new \RuntimeException('The organizations table is missing. Run php artisan migrate.');
    }
}
