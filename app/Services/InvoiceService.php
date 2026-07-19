<?php

namespace App\Services;

use App\Repositories\InvoiceRepository;

class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepository $repository)
    {
        parent::__construct($repository);
    }
}
