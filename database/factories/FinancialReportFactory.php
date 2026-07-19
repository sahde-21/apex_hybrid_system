<?php

namespace Database\Factories;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Models\FinancialReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialReport>
 */
class FinancialReportFactory extends Factory
{
    protected $model = FinancialReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('FR-####-??'),
            'name' => fake()->words(3, true),
            'report_type' => fake()->randomElement(FinancialReportType::cases()),
            'period_start' => fake()->date(),
            'period_end' => fake()->date(),
            'status' => fake()->randomElement(FinancialReportStatus::cases()),
            'total_revenue' => fake()->randomFloat(2, 0, 100000),
            'total_expenses' => fake()->randomFloat(2, 0, 50000),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}