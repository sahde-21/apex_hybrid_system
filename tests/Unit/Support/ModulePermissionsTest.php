<?php

use App\Support\ModulePermissions;

it('defines the expected erp actions', function () {
    expect(ModulePermissions::ACTIONS)->toContain('read', 'create', 'update', 'delete', 'approve', 'export', 'print');
});

it('generates a permission for every module and action', function () {
    $permissions = ModulePermissions::allPermissions();
    $expected = count(ModulePermissions::MODULES) * count(ModulePermissions::ACTIONS)
        + count(ModulePermissions::EXTRA_PERMISSIONS);

    expect(count($permissions))->toBe($expected)
        ->and($permissions)->toContain('dashboard.read')
        ->and($permissions)->toContain('products.export')
        ->and($permissions)->toContain('invoices.print')
        ->and($permissions)->toContain('pos.create')
        ->and($permissions)->toContain('users.approve')
        ->and($permissions)->toContain('journal-entries.post')
        ->and($permissions)->toContain('fiscal-periods.manage');
});

it('returns unique permission names', function () {
    $permissions = ModulePermissions::allPermissions();

    expect($permissions)->toHaveCount(count(array_unique($permissions)));
});
