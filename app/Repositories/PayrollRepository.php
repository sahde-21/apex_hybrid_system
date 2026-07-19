<?php

namespace App\Repositories;

use App\Models\Payroll;

/**
 * @extends BaseRepository<Payroll>
 */
class PayrollRepository extends BaseRepository
{
    protected string $model = Payroll::class;
}
