<?php

namespace App\Support\Inventory;

final class OpeningStockIdentity
{
    public function __construct(
        public int $warehouseId,
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public string $idempotencyKey,
        public ?string $sku = null,
        public bool $productActive = true,
        public bool $variantActive = true,
    ) {}
}
