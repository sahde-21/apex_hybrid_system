<?php

namespace App\Exceptions\Api;

use Exception;
use Throwable;

class IdempotencyConflictException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 409,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : __('scf.api.idempotency_conflict'), $code, $previous);
    }
}
