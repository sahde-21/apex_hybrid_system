<?php

namespace App\Services;

use App\Repositories\GiftCardRepository;

class GiftCardService extends BaseService
{
    public function __construct(GiftCardRepository $repository)
    {
        parent::__construct($repository);
    }
}
