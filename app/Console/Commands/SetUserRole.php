<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:set-role {email} {role : user or admin}';

    protected $description = 'Set the platform role of a user';

    public function handle(): int
    {
        $role = UserRole::tryFrom($this->input()->string('role')->lower()->toString());

        if ($role === null) {
            $this->error('Le role doit etre "user" ou "admin".');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Utilisateur introuvable.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();

        $this->info("Le role {$role->value} a ete attribue a {$user->email}.");

        return self::SUCCESS;
    }
}
