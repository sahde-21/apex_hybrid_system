<?php

namespace App\Repositories;

use App\Models\JournalEntry;

/**
 * @extends BaseRepository<JournalEntry>
 */
class JournalEntryRepository extends BaseRepository
{
    protected string $model = JournalEntry::class;
}
