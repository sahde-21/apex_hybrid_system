<?php

namespace App\Repositories;

use App\Models\Quotation;

/**
 * @extends BaseRepository<Quotation>
 */
class QuotationRepository extends BaseRepository
{
    protected string $model = Quotation::class;
}
