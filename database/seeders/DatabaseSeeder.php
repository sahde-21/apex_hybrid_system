<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    if (app()->isProduction()) {
      $this->call(ProductionSeeder::class);

      return;
    }

    $this->call(DemoSeeder::class);
    $this->call(AccountingSeeder::class);
  }
}
