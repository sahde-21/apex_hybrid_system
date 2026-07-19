<?php

namespace App\Repositories;

use App\Models\TaxRate;

/**
 * @extends BaseRepository<TaxRate>
 */
class TaxRateRepository extends BaseRepository
{
    protected string $model = TaxRate::class;
}
