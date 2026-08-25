<?php

namespace App\Exceptions\Inventory;

class InactiveWarehouseException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Warehouse is inactive.'));
    }
}
