<?php

use App\Models\User;
use App\Support\Database\DatabaseBackupService;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('release metadata config exposes safe defaults', function () {
    expect(config('release.version'))->toBeString()
        ->and(config('release.api_version'))->not->toBeEmpty();
});

test('release info command runs', function () {
    Artisan::call('scf:release-info');

    expect(Artisan::output())->toContain('Release information');
});

test('release info command supports json output', function () {
    Artisan::call('scf:release-info', ['--json' => true]);
    $payload = json_decode(Artisan::output(), true);

    expect($payload)->toBeArray()
        ->and($payload['version'])->not->toBeEmpty();
});

test('deploy check command runs read only checks', function () {
    Artisan::call('scf:deploy-check');

    expect(Artisan::output())->toContain('Pre-deployment validation');
});

test('deploy verify command runs post deployment checks', function () {
    Artisan::call('scf:deploy-verify');

    expect(Artisan::output())->toContain('Post-deployment verification');
});

test('release readiness command evaluates categories', function () {
    Artisan::call('scf:release-readiness', ['--json' => true]);
    $payload = json_decode(Artisan::output(), true);

    expect($payload['readiness'])->toBeIn(['ready', 'ready_with_warnings', 'not_ready'])
        ->and($payload['checks'])->toBeArray();
});

test('deploy plan command prints workflow without executing', function () {
    $this->artisan('scf:deploy-plan')
        ->expectsOutputToContain('Safe deployment workflow')
        ->expectsOutputToContain('php artisan migrate --force')
        ->assertExitCode(0);
});

test('migration inspect command reports pending migrations', function () {
    Artisan::call('scf:migrations:inspect', ['--json' => true]);
    $payload = json_decode(Artisan::output(), true);

    expect($payload)->toHaveKeys(['pending', 'applied', 'risky']);
});

test('queue status command reports driver', function () {
    Artisan::call('scf:queue-status', ['--json' => true]);
    $payload = json_decode(Artisan::output(), true);

    expect($payload['driver'])->not->toBeEmpty();
});

test('schedule list command reports registered tasks', function () {
    Artisan::call('scf:schedule:list');

    expect(Artisan::output())->toContain('Registered scheduled operations');
});

test('create admin command creates super admin securely', function () {
    Artisan::call('scf:create-admin', [
        '--name' => 'Production Admin',
        '--email' => 'prod-admin@example.com',
        '--password' => 'Str0ng-Passw0rd!',
        '--force' => true,
    ]);

    $user = User::query()->where('email', 'prod-admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('Str0ng-Passw0rd!', $user->password))->toBeTrue();
});

test('create admin command refuses duplicate email', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $exit = Artisan::call('scf:create-admin', [
        '--name' => 'Dup',
        '--email' => 'dup@example.com',
        '--password' => 'Str0ng-Passw0rd!',
        '--force' => true,
    ]);

    expect($exit)->toBe(1);
});

test('backup list and verify commands work with sqlite backup', function () {
    $database = database_path('testing_backup_command.sqlite');
    if (is_file($database)) {
        unlink($database);
    }

    $pdo = new PDO('sqlite:'.$database);
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    config(['database.connections.sqlite.database' => $database, 'database.default' => 'sqlite']);

    $service = app(DatabaseBackupService::class);
    $path = $service->backup('test-backup');
    $filename = basename($path);

    Artisan::call('scf:backup:list');
    expect(Artisan::output())->toContain($filename);

    $exit = Artisan::call('scf:backup:verify', ['backup' => $filename]);
    expect($exit)->toBe(0);

    if (is_file($database)) {
        unlink($database);
    }
});

test('backup restore command defaults to dry run', function () {
    $database = database_path('testing_restore_command.sqlite');
    if (is_file($database)) {
        unlink($database);
    }

    $pdo = new PDO('sqlite:'.$database);
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    config(['database.connections.sqlite.database' => $database, 'database.default' => 'sqlite']);

    $service = app(DatabaseBackupService::class);
    $filename = basename($service->backup('restore-dry-run'));

    Artisan::call('scf:backup:restore', ['backup' => $filename]);

    expect(Artisan::output())->toContain('dry-run');

    if (is_file($database)) {
        unlink($database);
    }
});

test('demo seeder refuses production environment', function () {
    app()['env'] = 'production';

    expect(fn () => (new DemoSeeder)->run())->toThrow(RuntimeException::class);
});

test('production seeder runs required seeders only', function () {
    $this->seed(ProductionSeeder::class);

    expect(\Spatie\Permission\Models\Role::query()->where('name', 'super-admin')->exists())->toBeTrue();
});

test('system information page requires settings permission', function () {
    $allowed = actingAsUserWithPermissions(['settings.read']);
    $denied = actingAsUserWithPermissions(['contacts.read']);

    $this->actingAs($allowed)->get(route('system-information.index'))->assertOk();
    $this->actingAs($denied)->get(route('system-information.index'))->assertForbidden();
});

test('api health endpoint includes release metadata', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonStructure(['data' => ['release' => ['version', 'release_name', 'api_version']]]);
});

test('localization release keys exist in all locales', function () {
    foreach (['en', 'ar', 'ckb'] as $locale) {
        expect(trans('scf.release.system_info_title', [], $locale))->not->toBe('scf.release.system_info_title');
        expect(trans('scf.release.deploy_check_heading', [], $locale))->not->toBe('scf.release.deploy_check_heading');
    }
});

test('deploy check json output does not expose credential values', function () {
    putenv('DB_PASSWORD=super-secret-test-value');
    config(['database.connections.sqlite.password' => 'super-secret-test-value']);

    Artisan::call('scf:deploy-check', ['--json' => true]);
    $output = Artisan::output();

    expect($output)->not->toContain('super-secret-test-value');
});

afterEach(function () {
    foreach (File::glob(database_path('backups/database_*_test-backup.sqlite')) ?: [] as $file) {
        File::delete($file);
    }
    foreach (File::glob(database_path('backups/database_*_restore-dry-run.sqlite')) ?: [] as $file) {
        File::delete($file);
    }
});
