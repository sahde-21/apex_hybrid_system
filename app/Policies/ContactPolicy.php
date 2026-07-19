<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.read');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('contacts.read');
    }

    public function create(User $user): bool
    {
        return $user->can('contacts.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('contacts.update');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('contacts.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('contacts.export');
    }

    public function print(User $user, Contact $contact): bool
    {
        return $user->can('contacts.print');
    }
}
