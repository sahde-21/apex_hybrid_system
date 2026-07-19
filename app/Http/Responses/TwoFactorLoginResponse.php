<?php

namespace App\Http\Responses;

use App\Support\PostLoginRedirect;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        return redirect()->intended(PostLoginRedirect::url($request->user()));
    }
}
