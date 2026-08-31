<?php

namespace App\Services;

use App\Repositories\CustomerFeedbackRepository;
use App\Models\CustomerFeedback;

/**
 * @extends BaseService<CustomerFeedback>
 */
class CustomerFeedbackService extends BaseService
{
    public function __construct(CustomerFeedbackRepository $repository)
    {
        parent::__construct($repository);
    }
}
