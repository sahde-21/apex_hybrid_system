<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\SupplierEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierEvaluation>
 */
class SupplierEvaluationFactory extends Factory
{
    protected $model = SupplierEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'evaluation_date' => fake()->date(),
            'quality_score' => fake()->numberBetween(1, 5),
            'delivery_score' => fake()->numberBetween(1, 5),
            'price_score' => fake()->numberBetween(1, 5),
            'overall_score' => fake()->numberBetween(1, 5),
            'comments' => fake()->paragraph(),
        ];
    }
}
