<?php

namespace App\Services;

use App\Repositories\JournalEntryRepository;

class JournalEntryService extends BaseService
{
    public function __construct(JournalEntryRepository $repository)
    {
        parent::__construct($repository);
    }
}
