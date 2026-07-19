<?php

namespace App\Repositories;

use App\Models\Contact;

/**
 * @extends BaseRepository<Contact>
 */
class ContactRepository extends BaseRepository
{
    protected string $model = Contact::class;
}
