<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('1###'),
            'name' => fake()->words(3, true),
            'parent_id' => null,
            'type' => AccountType::Asset,
            'normal_balance' => NormalBalance::Debit,
            'currency_code' => 'IQD',
            'branch_id' => null,
            'is_active' => true,
            'is_system' => false,
            'allow_manual_entry' => true,
            'system_key' => null,
            'description' => null,
            'opening_balance' => 0,
        ];
    }
}
