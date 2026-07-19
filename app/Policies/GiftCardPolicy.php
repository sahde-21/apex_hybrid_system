<?php

namespace App\Policies;

use App\Models\GiftCard;
use App\Models\User;

class GiftCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gift-cards.read');
    }

    public function view(User $user, GiftCard $giftCard): bool
    {
        return $user->can('gift-cards.read');
    }

    public function create(User $user): bool
    {
        return $user->can('gift-cards.create');
    }

    public function update(User $user, GiftCard $giftCard): bool
    {
        return $user->can('gift-cards.update');
    }

    public function delete(User $user, GiftCard $giftCard): bool
    {
        return $user->can('gift-cards.delete');
    }
}
