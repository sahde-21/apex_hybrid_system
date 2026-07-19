<?php

namespace App\Repositories;

use App\Models\ManagedDocument;

/**
 * @extends BaseRepository<ManagedDocument>
 */
class ManagedDocumentRepository extends BaseRepository
{
    protected string $model = ManagedDocument::class;
}
