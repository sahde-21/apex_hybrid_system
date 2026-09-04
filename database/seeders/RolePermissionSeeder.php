<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\ModulePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** @var array<int, string> */
    protected array $superAdminEmails = [
        'admin@scf.com',
        'test@example.com',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (ModulePermissions::allPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $allPermissions = Permission::query()->where('guard_name', 'web')->get();

        if ($allPermissions->isEmpty()) {
            throw new \RuntimeException('RolePermissionSeeder: no permissions were created.');
        }

        $roleMap = [
            'super-admin' => $allPermissions->pluck('name')->all(),
            'owner' => $allPermissions->pluck('name')->all(),
            'manager' => $this->managerPermissions(),
            'cashier' => array_values(array_unique([
                ...$this->prefixPermissions(['sale-orders', 'quotations', 'invoices', 'payments', 'contacts', 'coupons', 'gift-cards', 'loyalty-programs', 'pos', 'products', 'tax-rates', 'documents']),
                'quotations.send',
                'sale-orders.confirm',
                'sale-orders.invoice',
                'invoices.issue',
                'payments.record',
                'payments.post',
            ])),
            'warehouse' => array_values(array_unique([
                ...$this->prefixPermissions(['products', 'warehouses', 'inventory-adjustments', 'stock-transfers', 'variants', 'shipping-methods', 'delivery-trips', 'documents']),
                'inventory-adjustments.approve',
                'stock-transfers.approve',
                'purchase-orders.read',
                'purchase-orders.receive',
                'purchase-orders.return',
            ])),
            'sales' => array_values(array_unique([
                ...$this->prefixPermissions(['sale-orders', 'quotations', 'invoices', 'contacts', 'leads', 'crm-interactions', 'customer-feedback', 'campaigns', 'loyalty-programs', 'coupons', 'pos', 'documents']),
                'quotations.send',
                'quotations.reject',
                'quotations.convert',
                'sale-orders.submit',
                'sale-orders.confirm',
                'sale-orders.invoice',
                'invoices.issue',
                'sale-orders.approve',
                'quotations.approve',
                'invoices.approve',
            ])),
            'hr' => array_values(array_unique([
                ...$this->prefixPermissions(['employees', 'payrolls', 'attendance', 'leave-requests', 'shift-management', 'performance-reviews', 'documents']),
                'leave-requests.approve',
                'leave-requests.delete',
                'workflow.submit',
                'workflow.approve',
                'workflow.reject',
                'workflow.cancel',
                'workflow.reopen',
                'workflow.archive',
            ])),
            'purchasing' => array_values(array_unique([
                ...$this->prefixPermissions(['purchase-orders', 'purchase-requests', 'rfqs', 'bills', 'supplier-evaluations', 'contacts', 'documents', 'payments']),
                'purchase-requests.submit',
                'purchase-requests.convert',
                'purchase-requests.approve',
                'rfqs.send',
                'rfqs.accept',
                'rfqs.approve',
                'purchase-orders.submit',
                'purchase-orders.confirm',
                'purchase-orders.approve',
                'purchase-orders.bill',
                'purchase-orders.receive',
                'purchase-orders.return',
                'bills.issue',
                'bills.approve',
                'payments.record',
                'payments.post',
            ])),
            'accountant' => array_values(array_unique([
                ...$this->prefixPermissions(['expenses', 'journal-entries', 'payments', 'tax-rates', 'financial-reports', 'fixed-assets', 'budgeting', 'bank-reconciliation', 'bills', 'invoices', 'documents', 'chart-of-accounts', 'ledgers', 'financial-statements', 'fiscal-periods', 'currencies']),
                'journal-entries.approve',
                'journal-entries.post',
                'journal-entries.reverse',
                'fiscal-periods.manage',
                'invoices.issue',
                'invoices.void',
                'bills.issue',
                'bills.void',
                'payments.record',
                'payments.post',
                'payments.reverse',
            ])),
            'customer-support' => $this->prefixPermissions(['tickets', 'knowledge-base', 'crm-interactions', 'contacts', 'customer-feedback', 'documents']),
        ];

        $activityExtras = [
            'activities.comment',
            'activities.edit_own',
            'activities.delete_own',
        ];

        foreach (['manager', 'sales', 'purchasing', 'accountant', 'hr', 'cashier'] as $roleName) {
            $roleMap[$roleName] = array_values(array_unique([
                ...$roleMap[$roleName],
                ...$this->prefixPermissions(['activities']),
                ...$activityExtras,
                'activities.internal_note',
            ]));
        }

        $roleMap['manager'] = array_values(array_unique([
            ...$roleMap['manager'],
            'activities.view_all',
            'activities.manage',
            'intelligence.view',
            'intelligence.executive.view',
            'intelligence.financial.view',
            'intelligence.sales.view',
            'intelligence.purchasing.view',
            'intelligence.inventory.view',
            'intelligence.customers.view',
            'intelligence.suppliers.view',
            'intelligence.operations.view',
            'intelligence.forecasts.view',
            'intelligence.alerts.view',
            'intelligence.alerts.manage',
            'intelligence.recommendations.view',
            'intelligence.recommendations.manage',
            'intelligence.assistant.use',
            'intelligence.export',
        ]));

        foreach ($roleMap as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        $superAdmin = Role::findByName('super-admin', 'web');
        if ($superAdmin->permissions()->count() === 0) {
            throw new \RuntimeException('RolePermissionSeeder: super-admin has zero permissions after sync.');
        }

        $this->assignSuperAdmins();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function assignSuperAdmins(): void
    {
        $superAdmin = Role::findByName('super-admin', 'web');

        // Idempotent: assign role without removing other roles or deleting users.
        User::query()
            ->whereIn('email', $this->superAdminEmails)
            ->each(function (User $user) use ($superAdmin): void {
                if (! $user->hasRole($superAdmin)) {
                    $user->assignRole($superAdmin);
                }
            });

        // Bootstrap: first user becomes Super Admin if nobody has the role yet (non-production only).
        if (! app()->isProduction() && ! User::role('super-admin')->exists()) {
            $first = User::query()->orderBy('id')->first();
            if ($first && ! $first->hasRole($superAdmin)) {
                $first->assignRole($superAdmin);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function managerPermissions(): array
    {
        return collect(ModulePermissions::allPermissions())
            ->reject(fn (string $p) => str_starts_with($p, 'users.') || str_starts_with($p, 'settings.'))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    protected function prefixPermissions(array $modules): array
    {
        $permissions = ['dashboard.read', 'notifications.read', 'analytics.read'];

        foreach ($modules as $module) {
            foreach (['read', 'create', 'update', 'print', 'export'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }
}
