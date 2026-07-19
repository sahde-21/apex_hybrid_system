<?php

namespace App\Repositories;

use App\Models\Campaign;

/**
 * @extends BaseRepository<Campaign>
 */
class CampaignRepository extends BaseRepository
{
    protected string $model = Campaign::class;
}
