<?php

namespace App\Services;

use App\Repositories\KnowledgeBaseArticleRepository;
use App\Models\KnowledgeBaseArticle;

/**
 * @extends BaseService<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleService extends BaseService
{
    public function __construct(KnowledgeBaseArticleRepository $repository)
    {
        parent::__construct($repository);
    }
}
