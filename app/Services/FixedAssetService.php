<?php

namespace App\Services;

use App\Repositories\FixedAssetRepository;

class FixedAssetService extends BaseService
{
    public function __construct(FixedAssetRepository $repository)
    {
        parent::__construct($repository);
    }
}
