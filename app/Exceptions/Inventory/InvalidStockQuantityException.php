<?php

namespace App\Exceptions\Inventory;

class InvalidStockQuantityException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Invalid stock quantity.'));
    }
}
