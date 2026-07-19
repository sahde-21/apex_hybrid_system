<?php

namespace App\Services;

use App\Repositories\CampaignRepository;

class CampaignService extends BaseService
{
    public function __construct(CampaignRepository $repository)
    {
        parent::__construct($repository);
    }
}
