<?php

namespace App\Services\Accounts;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AccountContext
{
    public function current(?User $user): ?Organization
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasTable('organization_user')) {
            return null;
        }

        if ($user === null) {
            return Organization::query()->where('type', 'demo')->latest()->first();
        }

        $organization = $user->organizations()->latest('organizations.created_at')->first();

        if ($organization !== null) {
            return $organization;
        }

        $organization = Organization::create([
            'name' => $user->name,
            'type' => 'personal',
            'owner_id' => $user->id,
        ]);

        $organization->members()->attach($user->id, ['role' => 'owner']);

        return $organization;
    }

    public function currentOrDemo(?User $user): Organization
    {
        $organization = $this->current($user);

        if ($organization !== null) {
            return $organization;
        }

        if (! Schema::hasTable('organizations')) {
            throw new \RuntimeException('The organizations table is missing. Run php artisan migrate.');
        }

        return Organization::create([
            'name' => 'Compte demo Mariwaze',
            'type' => 'demo',
        ]);
    }
}
