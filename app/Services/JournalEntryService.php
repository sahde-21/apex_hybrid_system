<?php

namespace App\Services;

use App\Repositories\JournalEntryRepository;
use App\Models\JournalEntry;

/**
 * @extends BaseService<JournalEntry>
 */
class JournalEntryService extends BaseService
{
    public function __construct(JournalEntryRepository $repository)
    {
        parent::__construct($repository);
    }
}
