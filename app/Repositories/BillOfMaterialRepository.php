<?php

namespace App\Repositories;

use App\Models\BillOfMaterial;

/**
 * @extends BaseRepository<BillOfMaterial>
 */
class BillOfMaterialRepository extends BaseRepository
{
    protected string $model = BillOfMaterial::class;
}
