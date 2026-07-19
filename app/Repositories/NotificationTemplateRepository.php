<?php

namespace App\Repositories;

use App\Models\NotificationTemplate;

/**
 * @extends BaseRepository<NotificationTemplate>
 */
class NotificationTemplateRepository extends BaseRepository
{
    protected string $model = NotificationTemplate::class;
}
