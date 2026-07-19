<?php

namespace App\Http\Responses;

use App\Support\PostLoginRedirect;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        return redirect()->intended(PostLoginRedirect::url($request->user()).'?verified=1');
    }
}
