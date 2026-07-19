<?php

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gross = fake()->randomFloat(2, 1000, 10000);
        $deductions = fake()->randomFloat(2, 0, 1000);

        return [
            'reference_number' => fake()->unique()->bothify('PR-####-??'),
            'employee_id' => Employee::factory(),
            'pay_period_start' => fake()->date(),
            'pay_period_end' => fake()->date(),
            'gross_amount' => $gross,
            'deductions' => $deductions,
            'net_amount' => max(0, $gross - $deductions),
            'status' => fake()->randomElement(PayrollStatus::cases()),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}