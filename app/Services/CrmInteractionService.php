<?php

namespace App\Services;

use App\Repositories\CrmInteractionRepository;
use App\Models\CrmInteraction;

/**
 * @extends BaseService<CrmInteraction>
 */
class CrmInteractionService extends BaseService
{
    public function __construct(CrmInteractionRepository $repository)
    {
        parent::__construct($repository);
    }
}
