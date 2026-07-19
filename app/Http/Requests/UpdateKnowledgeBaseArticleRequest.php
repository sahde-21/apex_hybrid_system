<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeBaseArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('knowledge-base.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('knowledgeBaseArticle')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('knowledge_base_articles', 'slug')->ignore($id)],
            'category' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
