<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Models\Payment;

/**
 * @extends BaseService<Payment>
 */
class PaymentService extends BaseService
{
    public function __construct(PaymentRepository $repository)
    {
        parent::__construct($repository);
    }
}
