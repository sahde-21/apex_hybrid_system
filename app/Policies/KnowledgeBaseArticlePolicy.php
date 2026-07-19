<?php

namespace App\Policies;

use App\Models\KnowledgeBaseArticle;
use App\Models\User;

class KnowledgeBaseArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('knowledge-base.read');
    }

    public function view(User $user, KnowledgeBaseArticle $knowledgeBaseArticle): bool
    {
        return $user->can('knowledge-base.read');
    }

    public function create(User $user): bool
    {
        return $user->can('knowledge-base.create');
    }

    public function update(User $user, KnowledgeBaseArticle $knowledgeBaseArticle): bool
    {
        return $user->can('knowledge-base.update');
    }

    public function delete(User $user, KnowledgeBaseArticle $knowledgeBaseArticle): bool
    {
        return $user->can('knowledge-base.delete');
    }
}
