<?php

namespace App\Services;

use App\Repositories\CrmInteractionRepository;

class CrmInteractionService extends BaseService
{
    public function __construct(CrmInteractionRepository $repository)
    {
        parent::__construct($repository);
    }
}
