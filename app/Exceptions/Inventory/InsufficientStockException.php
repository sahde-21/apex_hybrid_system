<?php

namespace App\Exceptions\Inventory;

class InsufficientStockException extends InventoryException
{
    public function __construct(
        string $message = '',
        public readonly int $available = 0,
        public readonly int $requested = 0,
    ) {
        parent::__construct($message !== '' ? $message : __('Insufficient stock available.'));
    }
}
