<?php

namespace App\Support\Inventory;

use App\Models\Warehouse;

final class OpeningStockPlan
{
    /**
     * @param  list<OpeningStockIdentity>  $identities
     * @param  list<string>  $blockers
     * @param  list<string>  $warnings
     * @param  list<array{product_id: int, sku: string|null, stock_quantity: int, variant_count: int}>  $orphans
     * @param  list<array{type: string, id: int, sku: string|null, stock_quantity: int}>  $negatives
     */
    public function __construct(
        public ?Warehouse $warehouse,
        public bool $warehouseResolved,
        public bool $wouldCreateWarehouse,
        public array $identities,
        public array $blockers,
        public array $warnings,
        public array $orphans,
        public array $negatives,
        public int $productsScanned,
        public int $variantsScanned,
        public int $zeroSkipped,
        public int $registersWithoutWarehouse,
        public int $existingOpeningMovements,
        public int $expectedNewMovements,
        public int $expectedTotalQuantity,
    ) {}

    public function isClean(): bool
    {
        return $this->blockers === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'warehouse_id' => $this->warehouse?->id,
            'warehouse_code' => $this->warehouse?->code,
            'warehouse_resolved' => $this->warehouseResolved,
            'would_create_warehouse' => $this->wouldCreateWarehouse,
            'products_scanned' => $this->productsScanned,
            'variants_scanned' => $this->variantsScanned,
            'identities' => count($this->identities),
            'expected_new_movements' => $this->expectedNewMovements,
            'expected_total_quantity' => $this->expectedTotalQuantity,
            'zero_skipped' => $this->zeroSkipped,
            'existing_opening_movements' => $this->existingOpeningMovements,
            'registers_without_warehouse' => $this->registersWithoutWarehouse,
            'orphans' => $this->orphans,
            'negatives' => $this->negatives,
            'blockers' => $this->blockers,
            'warnings' => $this->warnings,
            'clean' => $this->isClean(),
        ];
    }
}
