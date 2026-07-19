<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncErpPermissionsCommand extends Command
{
    protected $signature = 'erp:sync-permissions
                            {--email=* : Email(s) to assign the super-admin role}
                            {--assign-defaults : Also assign super-admin to configured bootstrap emails}
                            {--assign-first : Assign super-admin to the first user if none exist}';

    protected $description = 'Idempotently restore ERP roles/permissions via RolePermissionSeeder (never deletes users)';

    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        $this->callSilent('db:seed', [
            '--class' => RolePermissionSeeder::class,
            '--force' => true,
        ]);

        $superAdmin = Role::findByName('super-admin', 'web');
        $permissionCount = $superAdmin->permissions()->count();

        if ($permissionCount === 0) {
            $this->error('super-admin has zero permissions after sync. Aborting.');

            return self::FAILURE;
        }

        $emails = $this->option('email') ?: [];

        if ($this->option('assign-defaults')) {
            $emails = array_values(array_unique(array_merge(
                $emails,
                ['admin@scf.com', 'test@example.com'],
            )));
        }

        foreach ($emails as $email) {
            $user = User::query()->where('email', $email)->first();
            if ($user && ! $user->hasRole($superAdmin)) {
                $user->assignRole($superAdmin);
                $this->info("Assigned super-admin to {$user->email}");
            }
        }

        if ($this->option('assign-first') && ! User::role('super-admin')->exists()) {
            $first = User::query()->orderBy('id')->first();
            if ($first && ! $first->hasRole($superAdmin)) {
                $first->assignRole($superAdmin);
                $this->info("Assigned super-admin to first user {$first->email}");
            }
        }

        $registrar->forgetCachedPermissions();

        $this->info("Permissions on super-admin: {$permissionCount}");
        $this->info('Super-admins: '.User::role('super-admin')->count());

        return self::SUCCESS;
    }
}