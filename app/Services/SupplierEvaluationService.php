<?php

namespace App\Services;

use App\Repositories\SupplierEvaluationRepository;

class SupplierEvaluationService extends BaseService
{
    public function __construct(SupplierEvaluationRepository $repository)
    {
        parent::__construct($repository);
    }
}
