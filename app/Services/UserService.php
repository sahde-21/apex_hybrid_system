<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string|int>  $roles
     * @param  list<string|int>  $permissions
     */
    public function createUser(array $data, array $roles = [], array $permissions = [], ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($data, $roles, $permissions, $avatar) {
            $data = $this->filterUserMassAssignment($data);

            if ($avatar) {
                $data['avatar_path'] = $this->storeAvatar($avatar);
            }

            $data['is_active'] ??= true;

            /** @var User $user */
            $user = $this->repository->create($data);
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            $this->syncAccess($user, $roles, $permissions);
            $this->audit($user, 'user_created');

            return $user->fresh(['roles', 'permissions']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string|int>|null  $roles
     * @param  list<string|int>|null  $permissions
     */
    public function updateUser(User $user, array $data, ?array $roles = null, ?array $permissions = null, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $roles, $permissions, $avatar) {
            $data = $this->filterUserMassAssignment($data);
            unset($data['password'], $data['email_verified_at']);

            if ($avatar) {
                $this->deleteAvatar($user);
                $data['avatar_path'] = $this->storeAvatar($avatar);
            }

            /** @var User $user */
            $user = $this->repository->update($user, $data);

            if ($roles !== null || $permissions !== null) {
                $this->syncAccess(
                    $user,
                    $roles ?? array_values($user->getRoleNames()->all()),
                    $permissions ?? array_values($user->getPermissionNames()->all()),
                );
            }

            $this->audit($user, 'user_updated');

            return $user->fresh(['roles', 'permissions']);
        });
    }

    /**
     * @param  array<int, string|int|mixed>  $roles
     * @return list<string|int>
     */
    public function sanitizeAssignableRoles(User $actor, array $roles): array
    {
        $privileged = config('security.privileged_roles', ['super-admin', 'owner']);

        return array_values(array_filter($roles, function ($role) use ($actor, $privileged): bool {
            if (! is_string($role) && ! is_int($role)) {
                return false;
            }

            if (is_string($role) && in_array($role, $privileged, true) && ! $actor->hasRole('super-admin')) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  array<int, string|int|mixed>  $permissions
     * @return list<string|int>
     */
    public function sanitizeAssignablePermissions(User $actor, array $permissions): array
    {
        if ($actor->hasRole('super-admin')) {
            return array_values($permissions);
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();

        return array_values(array_filter($permissions, function ($permission) use ($actorPermissions): bool {
            if (! is_string($permission)) {
                return false;
            }

            return in_array($permission, $actorPermissions, true);
        }));
    }

    public function changePassword(User $user, string $password, bool $forceReset = false): User
    {
        $user->forceFill([
            'password' => $password,
            'force_password_reset' => $forceReset,
        ])->save();

        $this->audit($user, 'password_changed');

        return $user;
    }

    public function forcePasswordReset(User $user): User
    {
        $user->forceFill(['force_password_reset' => true])->save();

        Password::broker()->sendResetLink(['email' => $user->email]);

        $this->audit($user, 'force_password_reset');

        return $user;
    }

    public function lock(User $user, ?string $reason = null): User
    {
        $user->forceFill([
            'locked_at' => now(),
            'locked_reason' => $reason ?: __('Account locked by administrator'),
        ])->save();

        $this->audit($user, 'user_locked', ['reason' => $user->locked_reason]);

        return $user;
    }

    public function unlock(User $user): User
    {
        $user->forceFill([
            'locked_at' => null,
            'locked_reason' => null,
        ])->save();

        $this->audit($user, 'user_unlocked');

        return $user;
    }

    public function activate(User $user): User
    {
        $user->forceFill(['is_active' => true])->save();
        $this->audit($user, 'user_activated');

        return $user;
    }

    public function deactivate(User $user): User
    {
        $user->forceFill(['is_active' => false])->save();
        $this->audit($user, 'user_deactivated');

        return $user;
    }

    public function softDelete(User $user): bool
    {
        $this->audit($user, 'user_deleted');

        return (bool) $user->delete();
    }

    public function restore(User $user): User
    {
        $user->restore();
        $this->audit($user, 'user_restored');

        return $user;
    }

    /**
     * @param  list<int>  $ids
     * @return int Number of affected users
     */
    public function bulkAction(string $action, array $ids, ?User $actor = null): int
    {
        $actor ??= Auth::user();
        $count = 0;

        User::query()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->when($actor, fn ($q) => $q->where('id', '!=', $actor->id))
            ->get()
            ->each(function (User $user) use ($action, &$count): void {
                match ($action) {
                    'activate' => $this->activate($user),
                    'deactivate' => $this->deactivate($user),
                    'lock' => $this->lock($user),
                    'unlock' => $this->unlock($user),
                    'delete' => $this->softDelete($user),
                    'restore' => $this->restore($user),
                    default => null,
                };
                $count++;
            });

        return $count;
    }

    public function recordLogin(User $user, bool $successful = true, string $event = 'login'): void
    {
        LoginHistory::query()->create([
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'event' => $event,
            'successful' => $successful,
            'logged_in_at' => now(),
        ]);

        if ($successful && $event === 'login') {
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
                'last_activity_at' => now(),
            ])->saveQuietly();
        }
    }

    public function touchActivity(User $user): void
    {
        $user->forceFill(['last_activity_at' => now()])->saveQuietly();
    }

    /**
     * @param  list<string|int>  $roles
     * @param  list<string|int>  $permissions
     */
    protected function syncAccess(User $user, array $roles, array $permissions): void
    {
        $actor = Auth::user();

        if ($actor instanceof User) {
            $roles = $this->sanitizeAssignableRoles($actor, $roles);
            $permissions = $this->sanitizeAssignablePermissions($actor, $permissions);
        }

        $roleModels = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($roles): void {
                $query->whereIn('name', $roles);

                $ids = array_values(array_filter($roles, fn ($role) => is_numeric($role)));
                if ($ids !== []) {
                    $query->orWhereIn('id', $ids);
                }
            })
            ->get();

        $user->syncRoles($roleModels);

        $permissionModels = Permission::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($permissions): void {
                $query->whereIn('name', $permissions);

                $ids = array_values(array_filter($permissions, fn ($permission) => is_numeric($permission)));
                if ($ids !== []) {
                    $query->orWhereIn('id', $ids);
                }
            })
            ->get();

        $user->syncPermissions($permissionModels);

        $this->audit($user, 'access_synced', [
            'roles' => $roleModels->pluck('name')->values()->all(),
            'permissions' => $permissionModels->pluck('name')->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterUserMassAssignment(array $data): array
    {
        return collect($data)
            ->only(['name', 'email', 'phone', 'password', 'is_active', 'avatar_path'])
            ->all();
    }

    protected function storeAvatar(UploadedFile $avatar): string
    {
        $this->assertSafeAvatar($avatar);

        $path = $avatar->store('avatars', 'public');

        return is_string($path) ? $path : '';
    }

    protected function assertSafeAvatar(UploadedFile $avatar): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        $mime = $avatar->getMimeType() ?: $avatar->getClientMimeType();
        $extension = strtolower($avatar->getClientOriginalExtension() ?: pathinfo($avatar->getClientOriginalName(), PATHINFO_EXTENSION));

        $extensionMimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];

        $compatible = $extensionMimeMap[$extension] ?? [];
        $realPath = $avatar->getRealPath();
        $imageInfo = is_string($realPath) && $realPath !== '' ? @getimagesize($realPath) : false;

        $valid = $mime !== ''
            && in_array($mime, $allowedMimes, true)
            && in_array($extension, $allowedExtensions, true)
            && in_array($mime, $compatible, true)
            && $imageInfo !== false;

        if (! $valid) {
            throw ValidationException::withMessages([
                'avatar' => __('validation.image', ['attribute' => 'avatar']),
            ]);
        }
    }

    protected function deleteAvatar(User $user): void
    {
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }

    /**
     * @param  array<string, mixed>|null  $extra
     */
    protected function audit(User $user, string $action, ?array $extra = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'action' => $action,
            'old_values' => null,
            'new_values' => $extra,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
