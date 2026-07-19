<?php

namespace Database\Factories;

use App\Enums\DeliveryTripStatus;
use App\Models\DeliveryTrip;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryTrip>
 */
class DeliveryTripFactory extends Factory
{
    protected $model = DeliveryTrip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('REF-######'),
            'shipping_method_id' => ShippingMethod::factory(),
            'driver_name' => fake()->word(),
            'vehicle_plate' => fake()->word(),
            'trip_date' => fake()->date(),
            'status' => fake()->randomElement(array_column(DeliveryTripStatus::cases(), 'value')),
            'notes' => fake()->paragraph(),
        ];
    }
}
