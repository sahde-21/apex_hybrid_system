<?php

namespace App\Services;

use App\Repositories\TicketRepository;

class TicketService extends BaseService
{
    public function __construct(TicketRepository $repository)
    {
        parent::__construct($repository);
    }
}
