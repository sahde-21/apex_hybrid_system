<?php

namespace App\Policies;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('journal-entries.read');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal-entries.read');
    }

    public function create(User $user): bool
    {
        return $user->can('journal-entries.create');
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal-entries.update') && $journalEntry->status === JournalEntryStatus::Draft;
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal-entries.delete') && $journalEntry->status === JournalEntryStatus::Draft;
    }

    public function post(User $user, JournalEntry $journalEntry): bool
    {
        return ($user->can('journal-entries.post') || $user->can('journal-entries.approve'))
            && $journalEntry->status === JournalEntryStatus::Draft;
    }

    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('journal-entries.reverse')
            && $journalEntry->status === JournalEntryStatus::Posted;
    }
}
