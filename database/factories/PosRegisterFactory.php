<?php

namespace Database\Factories;

use App\Models\PosRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosRegister>
 */
class PosRegisterFactory extends Factory
{
    protected $model = PosRegister::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Register '.fake()->unique()->numerify('##'),
            'code' => fake()->unique()->bothify('REG-##??'),
            'warehouse_id' => null,
            'branch_id' => null,
            'is_active' => true,
            'cash_drawer_enabled' => true,
            'notes' => null,
        ];
    }
}
