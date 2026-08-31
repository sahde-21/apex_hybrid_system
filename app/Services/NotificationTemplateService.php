<?php

namespace App\Services;

use App\Repositories\NotificationTemplateRepository;
use App\Models\NotificationTemplate;

/**
 * @extends BaseService<NotificationTemplate>
 */
class NotificationTemplateService extends BaseService
{
    public function __construct(NotificationTemplateRepository $repository)
    {
        parent::__construct($repository);
    }
}
