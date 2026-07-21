<?php

namespace App\Exceptions\Api;

use Exception;
use Throwable;

class BusinessConflictException extends Exception
{
    /**
     * @param  array<string, list<string>>|null  $errors
     */
    public function __construct(
        string $message = '',
        protected ?array $errors = null,
        int $code = 409,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : __('scf.api.business_conflict'), $code, $previous);
    }

    /**
     * @return array<string, list<string>>|null
     */
    public function errors(): ?array
    {
        return $this->errors;
    }
}
