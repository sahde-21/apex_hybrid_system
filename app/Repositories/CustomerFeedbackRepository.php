<?php

namespace App\Repositories;

use App\Models\CustomerFeedback;

/**
 * @extends BaseRepository<CustomerFeedback>
 */
class CustomerFeedbackRepository extends BaseRepository
{
    protected string $model = CustomerFeedback::class;
}
