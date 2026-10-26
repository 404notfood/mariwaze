<?php

namespace App\Services\Accounts;

use App\Models\Boat;
use App\Models\Cruise;
use App\Models\User;

/**
 * Croisière et bateau « courants » d'un utilisateur.
 *
 * Sans utilisateur authentifié, aucune ressource privée n'est résolue.
 */
class CurrentContext
{
    public function __construct(private readonly AccountContext $accounts) {}

    public function cruise(?User $user): ?Cruise
    {
        if ($user === null) {
            return null;
        }

        $organization = $this->accounts->currentOrCreate($user);
        $query = Cruise::query()
            ->with(['boat', 'selectedRoute.segments'])
            ->where('organization_id', $organization->id);

        return $query->latest()->first();
    }

    public function boat(?User $user, ?Cruise $cruise = null): ?Boat
    {
        if ($cruise?->boat !== null) {
            return $cruise->boat;
        }

        if ($user === null) {
            return null;
        }

        $organization = $this->accounts->currentOrCreate($user);
        $query = Boat::query()->where('organization_id', $organization->id);

        $boat = $query->latest()->first();

        return $boat instanceof Boat ? $boat : null;
    }
}
