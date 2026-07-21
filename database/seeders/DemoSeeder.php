<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Optional demo and development data. Never run in production.
 */
class DemoSeeder extends Seeder
{
  public function run(): void
  {
    if (app()->isProduction()) {
      throw new \RuntimeException('DemoSeeder must not run in production.');
    }

    User::query()->firstOrCreate(
      ['email' => 'admin@scf.com'],
      [
        'name' => 'SCF Admin',
        'password' => 'password',
        'email_verified_at' => now(),
      ],
    );

    User::query()->firstOrCreate(
      ['email' => 'test@example.com'],
      [
        'name' => 'Test User',
        'password' => 'password',
        'email_verified_at' => now(),
      ],
    );

    $this->call(RolePermissionSeeder::class);
    $this->call(PosSeeder::class);
    $this->call(PortalCustomerSeeder::class);
    $this->call(PortalSupplierSeeder::class);
  }
}
