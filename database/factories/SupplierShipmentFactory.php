<?php

namespace Database\Factories;

use App\Enums\SupplierShipmentStatus;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use App\Models\SupplierShipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupplierShipment>
 */
class SupplierShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'SHP-'.strtoupper(Str::random(8)),
            'contact_id' => Contact::factory()->supplier(),
            'purchase_order_id' => function (array $attributes) {
                return PurchaseOrder::factory()->create([
                    'contact_id' => $attributes['contact_id'],
                ])->id;
            },
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'shipped_at' => null,
            'carrier' => fake()->optional()->company(),
            'tracking_number' => null,
            'status' => SupplierShipmentStatus::Scheduled,
            'notes' => null,
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => SupplierShipmentStatus::Shipped,
            'shipped_at' => now(),
            'tracking_number' => strtoupper(Str::random(12)),
        ]);
    }
}
