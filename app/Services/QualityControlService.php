<?php

namespace App\Services;

use App\Repositories\QualityControlRepository;
use App\Models\QualityControl;

/**
 * @extends BaseService<QualityControl>
 */
class QualityControlService extends BaseService
{
    public function __construct(QualityControlRepository $repository)
    {
        parent::__construct($repository);
    }
}
