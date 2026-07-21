<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Required system data for production deployments.
 *
 * Run with: php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
  public function run(): void
  {
    $this->call(RolePermissionSeeder::class);
    $this->call(AccountingSeeder::class);
  }
}
