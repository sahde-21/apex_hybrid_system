<?php

namespace App\Services;

use App\Repositories\LeadRepository;
use App\Models\Lead;

/**
 * @extends BaseService<Lead>
 */
class LeadService extends BaseService
{
    public function __construct(LeadRepository $repository)
    {
        parent::__construct($repository);
    }
}
