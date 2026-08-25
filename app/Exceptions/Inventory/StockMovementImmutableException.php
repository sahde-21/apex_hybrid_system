<?php

namespace App\Exceptions\Inventory;

class StockMovementImmutableException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Stock movements are immutable.'));
    }
}
