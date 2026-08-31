<?php

namespace App\Services;

use App\Repositories\QuotationRepository;
use App\Models\Quotation;

/**
 * @extends BaseService<Quotation>
 */
class QuotationService extends BaseService
{
    public function __construct(QuotationRepository $repository)
    {
        parent::__construct($repository);
    }
}
