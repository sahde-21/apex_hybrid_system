<?php

namespace Database\Seeders;

use App\Models\PosRegister;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        PosRegister::query()->firstOrCreate(
            ['code' => 'POS-01'],
            [
                'name' => 'Front Counter',
                'is_active' => true,
                'cash_drawer_enabled' => true,
            ],
        );

        $categories = [
            ['code' => 'GEN', 'name' => 'General', 'sort_order' => 1],
            ['code' => 'BEV', 'name' => 'Beverages', 'sort_order' => 2],
            ['code' => 'FOOD', 'name' => 'Food', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
