<?php

namespace App\Services;

use App\Repositories\LeadRepository;

class LeadService extends BaseService
{
    public function __construct(LeadRepository $repository)
    {
        parent::__construct($repository);
    }
}
