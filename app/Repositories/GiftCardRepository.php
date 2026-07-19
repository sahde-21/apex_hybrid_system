<?php

namespace App\Repositories;

use App\Models\GiftCard;

/**
 * @extends BaseRepository<GiftCard>
 */
class GiftCardRepository extends BaseRepository
{
    protected string $model = GiftCard::class;
}
