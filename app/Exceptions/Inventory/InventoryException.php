<?php

namespace App\Exceptions\Inventory;

use RuntimeException;
use Throwable;

class InventoryException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : __('Inventory operation failed.'), $code, $previous);
    }
}
