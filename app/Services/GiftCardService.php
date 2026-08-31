<?php

namespace App\Services;

use App\Repositories\GiftCardRepository;
use App\Models\GiftCard;

/**
 * @extends BaseService<GiftCard>
 */
class GiftCardService extends BaseService
{
    public function __construct(GiftCardRepository $repository)
    {
        parent::__construct($repository);
    }
}
