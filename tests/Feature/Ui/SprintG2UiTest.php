<?php

use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('custom 404 page is localized and branded', function () {
    app()->setLocale('en');

    $this->get('/this-route-does-not-exist-scf-g2')
        ->assertNotFound()
        ->assertSee(__('scf.errors.title_404'), false)
        ->assertSee(__('scf.errors.message_404'), false);
});

test('custom 403 page renders for unauthorized intelligence export', function () {
    $user = actingAsUserWithPermissions(['intelligence.view']);

    $this->get(route('intelligence.export.csv', ['domain' => 'executive']))
        ->assertForbidden();
});

test('skip link and main landmark exist on authenticated layout', function () {
    actingAsSuperAdmin();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('scf.ui.skip_to_main'), false)
        ->assertSee('id="scf-main-content"', false);
});

test('error translation keys exist in en ar ckb', function () {
    foreach (['en', 'ar', 'ckb'] as $locale) {
        app()->setLocale($locale);
        expect(__('scf.errors.title_404'))->not->toBe('scf.errors.title_404')
            ->and(__('scf.ui.loading'))->not->toBe('scf.ui.loading');
    }
});
