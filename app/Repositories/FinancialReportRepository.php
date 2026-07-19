<?php

namespace App\Repositories;

use App\Models\FinancialReport;

/**
 * @extends BaseRepository<FinancialReport>
 */
class FinancialReportRepository extends BaseRepository
{
    protected string $model = FinancialReport::class;
}
