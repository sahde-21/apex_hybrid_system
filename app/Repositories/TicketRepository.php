<?php

namespace App\Repositories;

use App\Models\Ticket;

/**
 * @extends BaseRepository<Ticket>
 */
class TicketRepository extends BaseRepository
{
    protected string $model = Ticket::class;
}
