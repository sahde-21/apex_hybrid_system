<?php

namespace App\Exceptions\Inventory;

class InactiveProductException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Product is inactive.'));
    }
}
