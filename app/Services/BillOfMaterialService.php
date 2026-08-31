<?php

namespace App\Services;

use App\Repositories\BillOfMaterialRepository;
use App\Models\BillOfMaterial;

/**
 * @extends BaseService<BillOfMaterial>
 */
class BillOfMaterialService extends BaseService
{
    public function __construct(BillOfMaterialRepository $repository)
    {
        parent::__construct($repository);
    }
}
