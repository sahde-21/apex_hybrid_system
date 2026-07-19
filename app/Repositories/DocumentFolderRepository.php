<?php

namespace App\Repositories;

use App\Models\DocumentFolder;

/**
 * @extends BaseRepository<DocumentFolder>
 */
class DocumentFolderRepository extends BaseRepository
{
    protected string $model = DocumentFolder::class;
}
