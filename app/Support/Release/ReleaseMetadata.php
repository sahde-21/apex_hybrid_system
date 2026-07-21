<?php

namespace App\Support\Release;

use Illuminate\Support\Facades\File;

class ReleaseMetadata
{
  /**
   * @return array<string, mixed>
   */
  public static function all(): array
  {
    return [
      'application' => (string) config('app.name'),
      'version' => (string) config('release.version'),
      'release_name' => (string) config('release.name'),
      'build_id' => config('release.build_id'),
      'commit_sha' => config('release.commit_sha'),
      'release_date' => config('release.release_date'),
      'environment' => (string) config('app.env'),
      'api_version' => (string) config('release.api_version'),
      'schema_version' => (string) config('release.schema_version'),
      'laravel_version' => app()->version(),
      'php_version' => PHP_VERSION,
      'debug' => (bool) config('app.debug'),
      'maintenance' => app()->isDownForMaintenance(),
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function runtime(): array
  {
    return [
      ...self::all(),
      'database_driver' => (string) config('database.default'),
      'cache_driver' => (string) config('cache.default'),
      'queue_driver' => (string) config('queue.default'),
      'session_driver' => (string) config('session.driver'),
      'filesystem_disk' => (string) config('filesystems.default'),
      'log_channel' => (string) config('logging.default'),
    ];
  }

  /**
   * Safe subset for public or authenticated health responses.
   *
   * @return array<string, mixed>
   */
  public static function public(): array
  {
    return [
      'version' => (string) config('release.version'),
      'release_name' => (string) config('release.name'),
      'api_version' => (string) config('release.api_version'),
    ];
  }

  public static function frontendBuildReady(): bool
  {
    return File::exists(public_path('build/manifest.json'));
  }
}
