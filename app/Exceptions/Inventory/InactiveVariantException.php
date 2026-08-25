<?php

namespace App\Exceptions\Inventory;

class InactiveVariantException extends InventoryException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Variant is inactive.'));
    }
}
