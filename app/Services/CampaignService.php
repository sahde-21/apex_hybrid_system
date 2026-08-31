<?php

namespace App\Services;

use App\Repositories\CampaignRepository;
use App\Models\Campaign;

/**
 * @extends BaseService<Campaign>
 */
class CampaignService extends BaseService
{
    public function __construct(CampaignRepository $repository)
    {
        parent::__construct($repository);
    }
}
