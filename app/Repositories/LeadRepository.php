<?php

namespace App\Repositories;

use App\Models\Lead;

/**
 * @extends BaseRepository<Lead>
 */
class LeadRepository extends BaseRepository
{
    protected string $model = Lead::class;
}
