<?php

namespace App\Repositories;

use App\Models\KnowledgeBaseArticle;

/**
 * @extends BaseRepository<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleRepository extends BaseRepository
{
    protected string $model = KnowledgeBaseArticle::class;
}
