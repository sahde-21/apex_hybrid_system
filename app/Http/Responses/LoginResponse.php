<?php

namespace App\Http\Responses;

use App\Support\PostLoginRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        return redirect()->intended(PostLoginRedirect::url($request->user()));
    }
}
