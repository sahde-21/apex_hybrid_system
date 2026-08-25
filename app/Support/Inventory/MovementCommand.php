<?php

namespace App\Support\Inventory;

use App\Enums\StockMovementType;
use DateTimeInterface;

final class MovementCommand
{
    public function __construct(
        public int $warehouseId,
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public int $reservedDelta,
        public StockMovementType $movementType,
        public string $idempotencyKey,
        public DateTimeInterface|string $occurredAt,
        public ?string $reasonCode = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?int $referenceLineId = null,
        public ?string $unitCost = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public bool $allowInactive = false,
    ) {}

    /**
     * @param  array{
     *     warehouse_id: int,
     *     product_id: int,
     *     variant_id?: int|null,
     *     quantity: int,
     *     reserved_delta?: int,
     *     movement_type: StockMovementType|string,
     *     idempotency_key: string,
     *     occurred_at?: DateTimeInterface|string|null,
     *     reason_code?: string|null,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     reference_line_id?: int|null,
     *     unit_cost?: float|int|string|null,
     *     notes?: string|null,
     *     created_by?: int|null,
     *     allow_inactive?: bool
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['movement_type'] instanceof StockMovementType
            ? $data['movement_type']
            : StockMovementType::from((string) $data['movement_type']);

        $unitCost = $data['unit_cost'] ?? null;

        return new self(
            warehouseId: (int) $data['warehouse_id'],
            productId: (int) $data['product_id'],
            variantId: array_key_exists('variant_id', $data) && $data['variant_id'] !== null
                ? (int) $data['variant_id']
                : null,
            quantity: (int) $data['quantity'],
            reservedDelta: (int) ($data['reserved_delta'] ?? 0),
            movementType: $type,
            idempotencyKey: (string) $data['idempotency_key'],
            occurredAt: $data['occurred_at'] ?? now(),
            reasonCode: $data['reason_code'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            referenceLineId: isset($data['reference_line_id']) ? (int) $data['reference_line_id'] : null,
            unitCost: $unitCost !== null ? number_format((float) $unitCost, 4, '.', '') : null,
            notes: $data['notes'] ?? null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
            allowInactive: (bool) ($data['allow_inactive'] ?? false),
        );
    }
}
