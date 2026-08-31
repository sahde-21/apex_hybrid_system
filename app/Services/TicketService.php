<?php

namespace App\Services;

use App\Repositories\TicketRepository;
use App\Models\Ticket;

/**
 * @extends BaseService<Ticket>
 */
class TicketService extends BaseService
{
    public function __construct(TicketRepository $repository)
    {
        parent::__construct($repository);
    }
}
