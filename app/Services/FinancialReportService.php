<?php

namespace App\Services;

use App\Repositories\FinancialReportRepository;
use App\Models\FinancialReport;

/**
 * @extends BaseService<FinancialReport>
 */
class FinancialReportService extends BaseService
{
    public function __construct(FinancialReportRepository $repository)
    {
        parent::__construct($repository);
    }
}
