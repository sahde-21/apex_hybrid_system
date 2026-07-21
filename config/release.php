<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Application Release Metadata
  |--------------------------------------------------------------------------
  |
  | Safe, non-secret release identifiers for deployment validation, health
  | endpoints, and the administration system-information view.
  |
  */

  'version' => env('APP_VERSION', '1.0.0'),

  'name' => env('APP_RELEASE', 'SCF Enterprise Suite 1.0'),

  'build_id' => env('APP_BUILD_ID'),

  'commit_sha' => env('APP_COMMIT_SHA'),

  'release_date' => env('APP_RELEASE_DATE'),

  'api_version' => env('API_VERSION', 'v1'),

  /*
  |--------------------------------------------------------------------------
  | Database Schema Version
  |--------------------------------------------------------------------------
  |
  | Updated manually when a major schema milestone ships. The deploy-check
  | command compares applied migrations against expectations.
  |
  */

  'schema_version' => env('APP_SCHEMA_VERSION', '2026.07'),

];
