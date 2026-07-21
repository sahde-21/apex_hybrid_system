<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Deployment Validation
  |--------------------------------------------------------------------------
  */

  'required_php_extensions' => [
    'bcmath',
    'ctype',
    'curl',
    'dom',
    'fileinfo',
    'json',
    'mbstring',
    'openssl',
    'pdo',
    'tokenizer',
    'xml',
  ],

  'required_tables' => [
    'users',
    'roles',
    'permissions',
    'jobs',
    'failed_jobs',
    'api_idempotency_keys',
  ],

  'required_roles' => [
    'super-admin',
    'owner',
    'manager',
  ],

  'writable_paths' => [
    'storage/app',
    'storage/framework',
    'storage/logs',
    'bootstrap/cache',
  ],

  'insecure_demo_emails' => [
    'admin@scf.com',
    'test@example.com',
  ],

  /*
  |--------------------------------------------------------------------------
  | Queue Status Thresholds
  |--------------------------------------------------------------------------
  */

  'queue' => [
    'pending_warning' => (int) env('DEPLOYMENT_QUEUE_PENDING_WARNING', 100),
    'pending_fail' => (int) env('DEPLOYMENT_QUEUE_PENDING_FAIL', 1000),
    'oldest_job_warning_minutes' => (int) env('DEPLOYMENT_QUEUE_OLDEST_WARNING', 60),
  ],

  /*
  |--------------------------------------------------------------------------
  | Backup & Restore Safeguards
  |--------------------------------------------------------------------------
  */

  'backup' => [
    'allowed_extensions' => ['sqlite', 'sql'],
    'pre_restore_label' => 'pre-restore-safety',
  ],

  /*
  |--------------------------------------------------------------------------
  | Log Retention (documentation + deploy-check guidance)
  |--------------------------------------------------------------------------
  */

  'logging' => [
    'recommended_channel' => env('LOG_CHANNEL', 'stack'),
    'retention_days' => (int) env('LOG_RETENTION_DAYS', 14),
  ],

  /*
  |--------------------------------------------------------------------------
  | Maintenance Mode
  |--------------------------------------------------------------------------
  */

  'maintenance' => [
    'retry_seconds' => (int) env('MAINTENANCE_RETRY_SECONDS', 60),
    'secret' => env('APP_MAINTENANCE_SECRET'),
  ],

  /*
  |--------------------------------------------------------------------------
  | Release Readiness Categories
  |--------------------------------------------------------------------------
  */

  'readiness_categories' => [
    'configuration',
    'database',
    'migrations',
    'permissions',
    'security',
    'queue',
    'scheduler',
    'cache',
    'storage',
    'backups',
    'api',
    'health',
    'assets',
    'logging',
    'documentation',
    'release_metadata',
  ],

];
