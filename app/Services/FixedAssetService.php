<?php

namespace App\Services;

use App\Repositories\FixedAssetRepository;
use App\Models\FixedAsset;

/**
 * @extends BaseService<FixedAsset>
 */
class FixedAssetService extends BaseService
{
    public function __construct(FixedAssetRepository $repository)
    {
        parent::__construct($repository);
    }
}
