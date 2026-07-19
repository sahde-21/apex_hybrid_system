<?php

use App\Support\ModulePermissions;

it('defines the expected erp actions', function () {
    expect(ModulePermissions::ACTIONS)->toContain('read', 'create', 'update', 'delete', 'approve', 'export', 'print');
});

it('generates a permission for every module and action', function () {
    $permissions = ModulePermissions::allPermissions();

    expect(count($permissions))->toBe(count(ModulePermissions::MODULES) * count(ModulePermissions::ACTIONS))
        ->and($permissions)->toContain('dashboard.read')
        ->and($permissions)->toContain('products.export')
        ->and($permissions)->toContain('invoices.print')
        ->and($permissions)->toContain('pos.create')
        ->and($permissions)->toContain('users.approve');
});

it('returns unique permission names', function () {
    $permissions = ModulePermissions::allPermissions();

    expect($permissions)->toHaveCount(count(array_unique($permissions)));
});
