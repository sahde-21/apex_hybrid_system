<?php

namespace App\Services;

use App\Repositories\SupplierEvaluationRepository;
use App\Models\SupplierEvaluation;

/**
 * @extends BaseService<SupplierEvaluation>
 */
class SupplierEvaluationService extends BaseService
{
    public function __construct(SupplierEvaluationRepository $repository)
    {
        parent::__construct($repository);
    }
}
