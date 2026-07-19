<?php

namespace App\Services;

use App\Repositories\KnowledgeBaseArticleRepository;

class KnowledgeBaseArticleService extends BaseService
{
    public function __construct(KnowledgeBaseArticleRepository $repository)
    {
        parent::__construct($repository);
    }
}
