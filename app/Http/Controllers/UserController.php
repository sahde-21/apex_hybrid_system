<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct(protected UserService $users) {}

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        [$roles, $permissions] = $this->resolvedAccessPayload($request, forCreate: true);

        $this->users->createUser(
            $request->safe()->only(['name', 'email', 'phone', 'password', 'is_active']),
            $roles,
            $permissions,
            $request->file('avatar'),
        );

        return redirect()
            ->route('users.index')
            ->with('status', __('User created successfully.'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        [$roles, $permissions] = $this->resolvedAccessPayload($request, forCreate: false, target: $user);

        $this->users->updateUser(
            $user,
            $request->safe()->only(['name', 'email', 'phone', 'is_active']),
            $roles,
            $permissions,
            $request->file('avatar'),
        );

        if ($request->filled('password')) {
            $this->authorize('changePassword', $user);
            $this->users->changePassword($user, (string) $request->input('password'));
        }

        return redirect()
            ->route('users.index')
            ->with('status', __('User updated successfully.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->users->softDelete($user);

        return redirect()
            ->route('users.index')
            ->with('status', __('User deleted successfully.'));
    }

    /**
     * @return array{0: list<string>|null, 1: list<string>|null}
     */
    protected function resolvedAccessPayload(StoreUserRequest|UpdateUserRequest $request, bool $forCreate, ?User $target = null): array
    {
        $actor = $request->user();
        abort_unless($actor !== null, 403);

        $canAssign = $forCreate
            ? $actor->can('users.approve')
            : ($target !== null && $actor->can('assignRoles', $target));

        if (! $canAssign) {
            return $forCreate ? [[], []] : [null, null];
        }

        /** @var list<string> $roles */
        $roles = array_values(array_filter(
            $request->input('roles', []),
            fn ($role) => is_string($role) && $role !== ''
        ));

        /** @var list<string> $permissions */
        $permissions = array_values(array_filter(
            $request->input('permissions', []),
            fn ($permission) => is_string($permission) && $permission !== ''
        ));

        return [
            $this->users->sanitizeAssignableRoles($actor, $roles),
            $this->users->sanitizeAssignablePermissions($actor, $permissions),
        ];
    }
}
