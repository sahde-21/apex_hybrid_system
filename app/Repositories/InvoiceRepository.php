<?php

namespace App\Repositories;

use App\Models\Invoice;

/**
 * @extends BaseRepository<Invoice>
 */
class InvoiceRepository extends BaseRepository
{
    protected string $model = Invoice::class;
}
