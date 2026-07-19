<?php

namespace App\Services;

use App\Repositories\ContactRepository;

class ContactService extends BaseService
{
    public function __construct(ContactRepository $repository)
    {
        parent::__construct($repository);
    }
}
