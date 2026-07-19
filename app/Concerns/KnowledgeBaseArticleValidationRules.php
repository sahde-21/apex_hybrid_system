<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait KnowledgeBaseArticleValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function knowledgeBaseArticleRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('knowledge_base_articles', 'slug')],
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function knowledgeBaseArticleUpdateRules(?int $knowledgeBaseArticleId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('knowledge_base_articles', 'slug')->ignore($knowledgeBaseArticleId)],
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
