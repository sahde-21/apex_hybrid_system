<?php

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Models\Invoice;

/**
 * @extends BaseService<Invoice>
 */
class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepository $repository)
    {
        parent::__construct($repository);
    }
}
