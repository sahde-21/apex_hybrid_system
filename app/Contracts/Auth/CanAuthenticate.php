<?php

namespace App\Contracts\Auth;

interface CanAuthenticate
{
    public function canAuthenticate(): bool;
}
