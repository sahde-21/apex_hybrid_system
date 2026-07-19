<?php

namespace App\Services;

use App\Repositories\QualityControlRepository;

class QualityControlService extends BaseService
{
    public function __construct(QualityControlRepository $repository)
    {
        parent::__construct($repository);
    }
}
