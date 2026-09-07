<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\PostLoginRedirect;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        return redirect()->intended(PostLoginRedirect::url($user instanceof User ? $user : null).'?verified=1');
    }
}
