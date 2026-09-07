<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\Api\ApiAbilities;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Sanctum\PersonalAccessToken;

trait AuthorizesApiAbilities
{
    protected function authorizeApiAbility(string $ability): void
    {
        $user = request()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException(__('Unauthenticated.'));
        }

        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token === null) {
            return;
        }

        if ($token->can(ApiAbilities::READ_ALL) || $token->can($ability)) {
            return;
        }

        throw new AuthorizationException(__('scf.api.insufficient_token_ability'));
    }

    protected function authorizeApiRead(string $domainAbility): void
    {
        $this->authorizeApiAbility($domainAbility);
    }

    protected function authorizeApiWrite(string $domainAbility): void
    {
        $this->authorizeApiAbility($domainAbility);
    }
}
