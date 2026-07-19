<?php

namespace App\Repositories;

use App\Models\FixedAsset;

/**
 * @extends BaseRepository<FixedAsset>
 */
class FixedAssetRepository extends BaseRepository
{
    protected string $model = FixedAsset::class;
}
