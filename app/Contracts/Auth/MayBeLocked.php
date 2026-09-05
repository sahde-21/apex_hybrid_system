<?php

namespace App\Contracts\Auth;

interface MayBeLocked
{
    public function isLocked(): bool;
}
