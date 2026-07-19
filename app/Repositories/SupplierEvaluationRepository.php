<?php

namespace App\Repositories;

use App\Models\SupplierEvaluation;

/**
 * @extends BaseRepository<SupplierEvaluation>
 */
class SupplierEvaluationRepository extends BaseRepository
{
    protected string $model = SupplierEvaluation::class;
}
