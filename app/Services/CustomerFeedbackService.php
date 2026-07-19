<?php

namespace App\Services;

use App\Repositories\CustomerFeedbackRepository;

class CustomerFeedbackService extends BaseService
{
    public function __construct(CustomerFeedbackRepository $repository)
    {
        parent::__construct($repository);
    }
}
