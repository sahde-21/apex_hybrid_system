<?php

namespace App\Repositories;

use App\Models\Variant;

/**
 * @extends BaseRepository<Variant>
 */
class VariantRepository extends BaseRepository
{
    protected string $model = Variant::class;
}
