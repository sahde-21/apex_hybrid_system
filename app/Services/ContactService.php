<?php

namespace App\Services;

use App\Repositories\ContactRepository;
use App\Models\Contact;

/**
 * @extends BaseService<Contact>
 */
class ContactService extends BaseService
{
    public function __construct(ContactRepository $repository)
    {
        parent::__construct($repository);
    }
}
