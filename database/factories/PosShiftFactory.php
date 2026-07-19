<?php

namespace Database\Factories;

use App\Enums\PosShiftStatus;
use App\Models\PosRegister;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosShift>
 */
class PosShiftFactory extends Factory
{
    protected $model = PosShift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pos_register_id' => PosRegister::factory(),
            'user_id' => User::factory(),
            'status' => PosShiftStatus::Open,
            'opening_float' => 100,
            'opened_at' => now(),
            'opening_notes' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => PosShiftStatus::Closed,
            'closing_cash' => 100,
            'expected_cash' => 100,
            'cash_difference' => 0,
            'closed_at' => now(),
        ]);
    }
}
