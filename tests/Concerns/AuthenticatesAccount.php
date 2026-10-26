<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\User;
use App\Services\Accounts\AccountContext;

trait AuthenticatesAccount
{
    protected User $accountUser;

    protected Organization $accountOrganization;

    protected function setUpAuthenticatesAccount(): void
    {
        $this->accountUser = User::factory()->create();
        $this->actingAs($this->accountUser);
        $this->accountOrganization = app(AccountContext::class)->currentOrCreate($this->accountUser);
    }
}
