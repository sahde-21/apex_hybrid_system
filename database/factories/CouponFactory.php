<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('??-####'),
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 0, 10000),
            'valid_from' => fake()->date(),
            'valid_until' => fake()->date(),
            'usage_limit' => fake()->numberBetween(1, 100),
            'usage_count' => 0,
            'is_active' => fake()->boolean(),
        ];
    }
}
