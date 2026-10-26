<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('admin/users', [
            'users' => User::query()
                ->withCount(['organizations', 'boats'])
                ->latest()
                ->paginate(25, ['id', 'name', 'email', 'role', 'created_at'])
                ->withQueryString(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        if ($request->user()->is($user) && $data['role'] !== UserRole::Admin->value) {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas retirer votre propre accès administrateur.',
            ]);
        }

        $before = ['role' => $user->role->value];
        $user->forceFill(['role' => UserRole::from($data['role'])])->save();
        $this->audit->record($request, 'admin.user.role_updated', $user, $before, [
            'role' => $user->role->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rôle utilisateur mis à jour.']);

        return to_route('admin.users');
    }
}
