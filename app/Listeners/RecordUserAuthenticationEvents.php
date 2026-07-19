<?php

namespace App\Listeners;

use App\Services\UserService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class RecordUserAuthenticationEvents
{
    public function __construct(protected UserService $users) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof \App\Models\User) {
            $this->users->recordLogin($event->user, true, 'login');
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof \App\Models\User) {
            $this->users->recordLogin($event->user, true, 'logout');
        }
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user;

        if ($user instanceof \App\Models\User) {
            $this->users->recordLogin($user, false, 'failed');
        }
    }
}
