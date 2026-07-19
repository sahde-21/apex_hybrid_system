<?php

namespace Database\Factories;

use App\Enums\PosSaleStatus;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosSale>
 */
class PosSaleFactory extends Factory
{
    protected $model = PosSale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $register = PosRegister::factory()->create();
        $user = User::factory()->create();
        $shift = PosShift::factory()->create([
            'pos_register_id' => $register->id,
            'user_id' => $user->id,
        ]);

        return [
            'reference_number' => fake()->unique()->bothify('POS-########'),
            'pos_shift_id' => $shift->id,
            'pos_register_id' => $register->id,
            'user_id' => $user->id,
            'contact_id' => null,
            'invoice_id' => null,
            'status' => PosSaleStatus::Completed,
            'is_return' => false,
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'tax_amount' => 10,
            'total_amount' => 110,
            'loyalty_points_earned' => 0,
            'loyalty_points_redeemed' => 0,
            'cash_drawer_opened' => false,
        ];
    }
}
