<?php

namespace App\Services;

use App\Repositories\FinancialReportRepository;

class FinancialReportService extends BaseService
{
    public function __construct(FinancialReportRepository $repository)
    {
        parent::__construct($repository);
    }
}
