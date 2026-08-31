<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\PostLoginRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        return redirect()->intended(PostLoginRedirect::url($user instanceof User ? $user : null));
    }
}
