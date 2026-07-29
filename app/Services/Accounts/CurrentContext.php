<?php

namespace App\Services\Accounts;

use App\Models\Boat;
use App\Models\Cruise;
use App\Models\User;

/**
 * Croisière et bateau « courants » d'un utilisateur.
 *
 * En local et en test, les pages restent accessibles sans authentification :
 * on retombe alors sur le dernier enregistrement créé.
 */
class CurrentContext
{
    public function cruise(?User $user): ?Cruise
    {
        $query = Cruise::query()->with(['boat', 'selectedRoute.segments']);

        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        return $query->latest()->first();
    }

    public function boat(?User $user, ?Cruise $cruise = null): ?Boat
    {
        if ($cruise?->boat !== null) {
            return $cruise->boat;
        }

        $query = Boat::query();

        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        $boat = $query->latest()->first();

        return $boat instanceof Boat ? $boat : null;
    }
}
