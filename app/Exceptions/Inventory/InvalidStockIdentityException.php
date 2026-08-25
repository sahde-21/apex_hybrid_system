<?php

namespace App\Exceptions\Inventory;

class InvalidStockIdentityException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Invalid stock identity.'));
    }
}
