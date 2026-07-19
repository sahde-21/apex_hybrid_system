<?php

namespace App\Repositories;

use App\Models\Payment;

/**
 * @extends BaseRepository<Payment>
 */
class PaymentRepository extends BaseRepository
{
    protected string $model = Payment::class;
}
