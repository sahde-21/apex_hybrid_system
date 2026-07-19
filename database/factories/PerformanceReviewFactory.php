<?php

namespace Database\Factories;

use App\Enums\PerformanceReviewStatus;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceReview>
 */
class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'review_date' => fake()->date(),
            'rating' => fake()->numberBetween(1, 5),
            'status' => fake()->randomElement(array_column(PerformanceReviewStatus::cases(), 'value')),
            'comments' => fake()->paragraph(),
        ];
    }
}
