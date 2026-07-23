<?php

namespace App\Services\Deployment;

use App\Support\Release\ReleaseMetadata;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Throwable;

class DeploymentCheckService
{
  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  public function preDeployChecks(bool $strictProduction = false): array
  {
    $checks = [];

    $checks[] = $this->checkEnvironment($strictProduction);
    $checks[] = $this->checkAppKey();
    $checks[] = $this->checkDebugMode();
    $checks[] = $this->checkAppUrl($strictProduction);
    $checks[] = $this->checkHttpsExpectation($strictProduction);
    $checks = array_merge($checks, $this->checkDatabaseConnectivity());
    $checks = array_merge($checks, $this->checkPendingMigrations());
    $checks[] = $this->driverCheck('cache', (string) config('cache.default'), 'cache');
    $checks[] = $this->driverCheck('queue', (string) config('queue.default'), 'queue');
    $checks[] = $this->driverCheck('session', (string) config('session.driver'), 'session');
    $checks[] = $this->driverCheck('mail', (string) config('mail.default'), 'configuration');
    $checks[] = $this->driverCheck('filesystem', (string) config('filesystems.default'), 'storage');
    $checks = array_merge($checks, $this->checkWritablePaths());
    $checks[] = $this->checkStorageLink();
    $checks = array_merge($checks, $this->checkQueueTables());
    $checks = array_merge($checks, $this->checkPhpExtensions());
    $checks[] = $this->checkFrontendManifest();
    $checks = array_merge($checks, $this->checkCacheCompatibility());
    $checks = array_merge($checks, $this->checkReleaseMetadata());
    $checks = array_merge($checks, $this->checkInsecureDemoUsers());
    $checks = array_merge($checks, $this->checkBackupDirectory());
    $checks[] = $this->checkSchedulerRegistration();

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  public function postDeployChecks(): array
  {
    $checks = [];

    $checks[] = $this->checkApplicationBoot();
    $checks = array_merge($checks, $this->checkDatabaseConnectivity());
    $checks = array_merge($checks, $this->checkMigrationsApplied());
    $checks[] = $this->checkCacheReadWrite();
    $checks[] = $this->checkQueueUsable();
    $checks = array_merge($checks, $this->checkWritablePaths());
    $checks = array_merge($checks, $this->checkHealthEndpoints());
    $checks = array_merge($checks, $this->checkReleaseMetadata());
    $checks = array_merge($checks, $this->checkConfigCacheState());
    $checks[] = $this->checkRouteCacheState();
    $checks[] = $this->checkViewsCompile();
    $checks[] = $this->checkAuthRouteExists();
    $checks = array_merge($checks, $this->checkCorePermissions());
    $checks = array_merge($checks, $this->checkRequiredRoles());
    $checks[] = $this->checkApiV1Routes();
    $checks[] = $this->checkSchedulerRegistration();
    $checks = array_merge($checks, $this->checkBackupConfiguration());

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  public function readinessChecks(): array
  {
    $pre = $this->preDeployChecks(app()->isProduction());
    $post = $this->postDeployChecks();

    $merged = [];
    foreach (array_merge($pre, $post) as $check) {
      $merged[$check['key']] = $check;
    }

    $checks = array_values($merged);

    $checks[] = $this->documentationCheck();
    $checks[] = $this->securityChecklistSample();

    return $checks;
  }

  /**
   * @param  list<array{key: string, status: string, message: string, category: string}>  $checks
   * @return array{status: string, failures: int, warnings: int, passes: int}
   */
  public function summarize(array $checks): array
  {
    $failures = collect($checks)->where('status', 'fail')->count();
    $warnings = collect($checks)->where('status', 'warn')->count();
    $passes = collect($checks)->where('status', 'pass')->count();

    $status = match (true) {
      $failures > 0 => 'not_ready',
      $warnings > 0 => 'ready_with_warnings',
      default => 'ready',
    };

    return [
      'status' => $status,
      'failures' => $failures,
      'warnings' => $warnings,
      'passes' => $passes,
    ];
  }

  /**
   * @return array{key: string, status: string, message: string, category: string}
   */
  protected function result(string $key, string $status, string $message, string $category = 'configuration'): array
  {
    return compact('key', 'status', 'message', 'category');
  }

  protected function checkEnvironment(bool $strictProduction): array
  {
    $env = (string) config('app.env');

    if ($strictProduction && $env !== 'production') {
      return $this->result('app_env', 'warn', __('scf.release.check_env_not_production', ['env' => $env]), 'configuration');
    }

    return $this->result('app_env', 'pass', __('scf.release.check_env_ok', ['env' => $env]), 'configuration');
  }

  protected function checkAppKey(): array
  {
    return $this->result(
      'app_key',
      config('app.key') ? 'pass' : 'fail',
      config('app.key') ? __('scf.release.check_app_key_ok') : __('scf.release.check_app_key_missing'),
      'security',
    );
  }

  protected function checkDebugMode(): array
  {
    if (app()->isProduction() && config('app.debug')) {
      return $this->result('app_debug', 'fail', __('scf.release.check_debug_production'), 'security');
    }

    return $this->result('app_debug', 'pass', __('scf.release.check_debug_ok'), 'security');
  }

  protected function checkAppUrl(bool $strictProduction): array
  {
    $url = (string) config('app.url');

    if ($url === '' || $url === 'http://localhost') {
      return $this->result('app_url', $strictProduction ? 'fail' : 'warn', __('scf.release.check_app_url_invalid'), 'configuration');
    }

    return $this->result('app_url', 'pass', __('scf.release.check_app_url_ok'), 'configuration');
  }

  protected function checkHttpsExpectation(bool $strictProduction): array
  {
    $url = (string) config('app.url');

    if ($strictProduction && ! str_starts_with($url, 'https://')) {
      return $this->result('https', 'warn', __('scf.release.check_https_recommended'), 'security');
    }

    return $this->result('https', 'pass', __('scf.release.check_https_ok'), 'security');
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkDatabaseConnectivity(): array
  {
    try {
      DB::connection()->getPdo();

      return [
        $this->result('database', 'pass', __('scf.release.check_database_ok', [
          'driver' => config('database.default'),
        ]), 'database'),
      ];
    } catch (Throwable) {
      return [
        $this->result('database', 'fail', __('scf.release.check_database_fail'), 'database'),
      ];
    }
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkPendingMigrations(): array
  {
    try {
      $pending = $this->pendingMigrationCount();

      if ($pending > 0) {
        return [
          $this->result('migrations_pending', 'warn', __('scf.release.check_migrations_pending', ['count' => $pending]), 'migrations'),
        ];
      }

      return [
        $this->result('migrations_pending', 'pass', __('scf.release.check_migrations_none_pending'), 'migrations'),
      ];
    } catch (Throwable) {
      return [
        $this->result('migrations_pending', 'fail', __('scf.release.check_migrations_unavailable'), 'migrations'),
      ];
    }
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkMigrationsApplied(): array
  {
    try {
      $pending = $this->pendingMigrationCount();

      if ($pending > 0) {
        return [
          $this->result('migrations_applied', 'fail', __('scf.release.check_migrations_not_applied', ['count' => $pending]), 'migrations'),
        ];
      }

      return [
        $this->result('migrations_applied', 'pass', __('scf.release.check_migrations_applied'), 'migrations'),
      ];
    } catch (Throwable) {
      return [
        $this->result('migrations_applied', 'fail', __('scf.release.check_migrations_unavailable'), 'migrations'),
      ];
    }
  }

  protected function pendingMigrationCount(): int
  {
    $migrator = app('migrator');
    $files = $migrator->getMigrationFiles(database_path('migrations'));
    $ran = $migrator->getRepository()->getRan();

    return count(array_diff(array_keys($files), $ran));
  }

  protected function driverCheck(string $key, string $driver, string $category): array
  {
    return $this->result("{$key}_driver", 'pass', __('scf.release.check_driver_ok', [
      'name' => $key,
      'driver' => $driver,
    ]), $category);
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkWritablePaths(): array
  {
    $checks = [];

    foreach (config('deployment.writable_paths', []) as $relative) {
      $path = base_path($relative);
      File::ensureDirectoryExists($path);
      $writable = is_writable($path);
      $checks[] = $this->result(
        'writable:'.str_replace('/', '_', $relative),
        $writable ? 'pass' : 'fail',
        $writable
          ? __('scf.release.check_writable_ok', ['path' => $relative])
          : __('scf.release.check_writable_fail', ['path' => $relative]),
        'storage',
      );
    }

    return $checks;
  }

  protected function checkStorageLink(): array
  {
    $link = public_path('storage');
    $exists = is_link($link) || is_dir($link);

    return $this->result(
      'storage_link',
      $exists ? 'pass' : 'warn',
      $exists ? __('scf.release.check_storage_link_ok') : __('scf.release.check_storage_link_missing'),
      'storage',
    );
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkQueueTables(): array
  {
    $checks = [];
    $driver = (string) config('queue.default');

    if ($driver === 'database') {
      $checks[] = $this->result(
        'queue_jobs_table',
        Schema::hasTable('jobs') ? 'pass' : 'fail',
        Schema::hasTable('jobs') ? __('scf.release.check_queue_jobs_ok') : __('scf.release.check_queue_jobs_missing'),
        'queue',
      );
    }

    $checks[] = $this->result(
      'failed_jobs_table',
      Schema::hasTable('failed_jobs') ? 'pass' : 'warn',
      Schema::hasTable('failed_jobs') ? __('scf.release.check_failed_jobs_ok') : __('scf.release.check_failed_jobs_missing'),
      'queue',
    );

    $checks[] = $this->result(
      'idempotency_table',
      Schema::hasTable('api_idempotency_keys') ? 'pass' : 'warn',
      Schema::hasTable('api_idempotency_keys') ? __('scf.release.check_idempotency_ok') : __('scf.release.check_idempotency_missing'),
      'api',
    );

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkPhpExtensions(): array
  {
    $checks = [];

    foreach (config('deployment.required_php_extensions', []) as $extension) {
      $loaded = extension_loaded($extension);
      $checks[] = $this->result(
        'php_ext:'.$extension,
        $loaded ? 'pass' : 'fail',
        $loaded
          ? __('scf.release.check_php_extension_ok', ['extension' => $extension])
          : __('scf.release.check_php_extension_missing', ['extension' => $extension]),
        'configuration',
      );
    }

    return $checks;
  }

  protected function checkFrontendManifest(): array
  {
    $ready = ReleaseMetadata::frontendBuildReady();

    return $this->result(
      'frontend_manifest',
      $ready ? 'pass' : (app()->isProduction() ? 'fail' : 'warn'),
      $ready ? __('scf.release.check_frontend_ok') : __('scf.release.check_frontend_missing'),
      'assets',
    );
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkCacheCompatibility(): array
  {
    $checks = [];
    $cacheDir = base_path('bootstrap/cache');
    $writable = is_dir($cacheDir) && is_writable($cacheDir);

    $checks[] = $this->result(
      'cache_directory',
      $writable ? 'pass' : 'fail',
      $writable ? __('scf.release.check_cache_dir_ok') : __('scf.release.check_cache_dir_fail'),
      'cache',
    );

    foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $command) {
      $registered = collect(Artisan::all())->has($command);
      $checks[] = $this->result(
        'cache_compat:'.str_replace(':', '_', $command),
        $registered ? 'pass' : 'warn',
        $registered
          ? __('scf.release.check_cache_compat_ok', ['command' => $command])
          : __('scf.release.check_cache_compat_warn', ['command' => $command]),
        'cache',
      );
    }

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkReleaseMetadata(): array
  {
    $version = (string) config('release.version');

    return [
      $this->result(
        'release_version',
        $version !== '' ? 'pass' : 'warn',
        $version !== '' ? __('scf.release.check_release_ok', ['version' => $version]) : __('scf.release.check_release_missing'),
        'release_metadata',
      ),
    ];
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkInsecureDemoUsers(): array
  {
    if (! app()->isProduction() || ! Schema::hasTable('users')) {
      return [];
    }

    $checks = [];

    foreach (config('deployment.insecure_demo_emails', []) as $email) {
      $user = DB::table('users')->where('email', $email)->first();

      if (! $user) {
        continue;
      }

      if (Hash::check('password', (string) $user->password)) {
        $checks[] = $this->result(
          'insecure_user:'.md5($email),
          'fail',
          __('scf.release.check_insecure_demo_user', ['email' => $email]),
          'security',
        );
      }
    }

    if ($checks === []) {
      $checks[] = $this->result('insecure_users', 'pass', __('scf.release.check_insecure_users_ok'), 'security');
    }

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkBackupDirectory(): array
  {
    $directory = database_path('backups');
    File::ensureDirectoryExists($directory);

    return [
      $this->result(
        'backup_directory',
        is_writable($directory) ? 'pass' : 'warn',
        is_writable($directory) ? __('scf.release.check_backup_dir_ok') : __('scf.release.check_backup_dir_fail'),
        'backups',
      ),
    ];
  }

  protected function checkSchedulerRegistration(): array
  {
    $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();

    return $this->result(
      'scheduler',
      count($events) > 0 ? 'pass' : 'warn',
      count($events) > 0
        ? __('scf.release.check_scheduler_ok', ['count' => count($events)])
        : __('scf.release.check_scheduler_empty'),
      'scheduler',
    );
  }

  protected function checkApplicationBoot(): array
  {
    return $this->result('application_boot', 'pass', __('scf.release.check_boot_ok'), 'configuration');
  }

  protected function checkCacheReadWrite(): array
  {
    try {
      $key = 'deploy:verify:'.uniqid('', true);
      Cache::put($key, 'ok', 10);
      $value = Cache::pull($key);

      return $this->result(
        'cache_rw',
        $value === 'ok' ? 'pass' : 'fail',
        $value === 'ok' ? __('scf.release.check_cache_rw_ok') : __('scf.release.check_cache_rw_fail'),
        'cache',
      );
    } catch (Throwable) {
      return $this->result('cache_rw', 'fail', __('scf.release.check_cache_rw_fail'), 'cache');
    }
  }

  protected function checkQueueUsable(): array
  {
    $driver = (string) config('queue.default');

    if ($driver === 'sync') {
      return $this->result('queue_usable', 'warn', __('scf.release.check_queue_sync'), 'queue');
    }

    return $this->result('queue_usable', 'pass', __('scf.release.check_queue_ok', ['driver' => $driver]), 'queue');
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkHealthEndpoints(): array
  {
    $checks = [];

    try {
      $health = app(\App\Services\Performance\HealthCheckService::class)->readiness(false);
      $checks[] = $this->result(
        'health_readiness',
        $health['status'] === 'ok' ? 'pass' : 'fail',
        $health['status'] === 'ok' ? __('scf.release.check_health_ok') : __('scf.release.check_health_degraded'),
        'health',
      );
    } catch (Throwable) {
      $checks[] = $this->result('health_readiness', 'fail', __('scf.release.check_health_fail'), 'health');
    }

    return $checks;
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkConfigCacheState(): array
  {
    $cached = is_file(base_path('bootstrap/cache/config.php'));

    if (app()->isProduction()) {
      return [
        $this->result(
          'config_cached',
          $cached ? 'pass' : 'warn',
          $cached ? __('scf.release.check_config_cached') : __('scf.release.check_config_not_cached'),
          'cache',
        ),
      ];
    }

    return [
      $this->result('config_cached', 'pass', __('scf.release.check_config_cache_skipped'), 'cache'),
    ];
  }

  protected function checkRouteCacheState(): array
  {
    $cached = is_file(base_path('bootstrap/cache/routes-v7.php'));

    return $this->result(
      'route_cached',
      $cached || ! app()->isProduction() ? 'pass' : 'warn',
      $cached ? __('scf.release.check_route_cached') : __('scf.release.check_route_not_cached'),
      'cache',
    );
  }

  protected function checkViewsCompile(): array
  {
    try {
      View::make('errors.503');

      return $this->result('views_compile', 'pass', __('scf.release.check_views_ok'), 'cache');
    } catch (Throwable) {
      return $this->result('views_compile', 'fail', __('scf.release.check_views_fail'), 'cache');
    }
  }

  protected function checkAuthRouteExists(): array
  {
    $hasLogin = collect(Route::getRoutes())->contains(fn ($route) => str_contains($route->uri(), 'login'));

    return $this->result(
      'auth_route',
      $hasLogin ? 'pass' : 'fail',
      $hasLogin ? __('scf.release.check_auth_route_ok') : __('scf.release.check_auth_route_missing'),
      'security',
    );
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkCorePermissions(): array
  {
    if (! Schema::hasTable('permissions')) {
      return [
        $this->result('permissions', 'fail', __('scf.release.check_permissions_missing'), 'permissions'),
      ];
    }

    $count = DB::table('permissions')->count();

    return [
      $this->result(
        'permissions',
        $count > 0 ? 'pass' : 'fail',
        $count > 0 ? __('scf.release.check_permissions_ok', ['count' => $count]) : __('scf.release.check_permissions_empty'),
        'permissions',
      ),
    ];
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkRequiredRoles(): array
  {
    $checks = [];

    foreach (config('deployment.required_roles', []) as $roleName) {
      $exists = Role::query()->where('name', $roleName)->exists();
      $checks[] = $this->result(
        'role:'.$roleName,
        $exists ? 'pass' : 'fail',
        $exists
          ? __('scf.release.check_role_ok', ['role' => $roleName])
          : __('scf.release.check_role_missing', ['role' => $roleName]),
        'permissions',
      );
    }

    return $checks;
  }

  protected function checkApiV1Routes(): array
  {
    $hasApi = collect(Route::getRoutes())->contains(fn ($route) => str_starts_with($route->uri(), 'api/v1'));

    return $this->result(
      'api_v1',
      $hasApi ? 'pass' : 'fail',
      $hasApi ? __('scf.release.check_api_v1_ok') : __('scf.release.check_api_v1_missing'),
      'api',
    );
  }

  /**
   * @return list<array{key: string, status: string, message: string, category: string}>
   */
  protected function checkBackupConfiguration(): array
  {
    try {
      $service = app(\App\Support\Database\DatabaseBackupService::class);
      $dir = $service->backupDirectory();

      return [
        $this->result('backup_config', 'pass', __('scf.release.check_backup_config_ok'), 'backups'),
      ];
    } catch (Throwable) {
      return [
        $this->result('backup_config', 'warn', __('scf.release.check_backup_config_warn'), 'backups'),
      ];
    }
  }

  protected function documentationCheck(): array
  {
    $required = ['docs/DEPLOYMENT.md', 'docs/RELEASE_PROCESS.md', 'CHANGELOG.md'];
    $missing = collect($required)->reject(fn ($path) => is_file(base_path($path)));

    return $this->result(
      'documentation',
      $missing->isEmpty() ? 'pass' : 'warn',
      $missing->isEmpty()
        ? __('scf.release.check_docs_ok')
        : __('scf.release.check_docs_missing', ['files' => $missing->implode(', ')]),
      'documentation',
    );
  }

  protected function securityChecklistSample(): array
  {
    $issues = [];

    if (app()->isProduction() && config('app.debug')) {
      $issues[] = 'debug';
    }

    if (app()->isProduction() && ! str_starts_with((string) config('app.url'), 'https://')) {
      $issues[] = 'https';
    }

    return $this->result(
      'security_baseline',
      $issues === [] ? 'pass' : 'warn',
      $issues === []
        ? __('scf.release.check_security_ok')
        : __('scf.release.check_security_warn', ['issues' => implode(', ', $issues)]),
      'security',
    );
  }
}
