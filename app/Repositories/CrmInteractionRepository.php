<?php

namespace App\Repositories;

use App\Models\CrmInteraction;

/**
 * @extends BaseRepository<CrmInteraction>
 */
class CrmInteractionRepository extends BaseRepository
{
    protected string $model = CrmInteraction::class;
}
