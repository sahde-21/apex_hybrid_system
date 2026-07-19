<?php

namespace App\Services;

use App\Repositories\NotificationTemplateRepository;

class NotificationTemplateService extends BaseService
{
    public function __construct(NotificationTemplateRepository $repository)
    {
        parent::__construct($repository);
    }
}
