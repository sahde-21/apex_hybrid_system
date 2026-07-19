<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKnowledgeBaseArticleRequest;
use App\Http\Requests\UpdateKnowledgeBaseArticleRequest;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseArticleService;
use Illuminate\Http\RedirectResponse;

class KnowledgeBaseArticleController extends Controller
{
    public function __construct(
        protected KnowledgeBaseArticleService $service,
    ) {}

    public function store(StoreKnowledgeBaseArticleRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('knowledge-base.index')
            ->with('status', __('Knowledge base created successfully.'));
    }

    public function update(UpdateKnowledgeBaseArticleRequest $request, KnowledgeBaseArticle $knowledgeBaseArticle): RedirectResponse
    {
        $this->service->update($knowledgeBaseArticle, $request->validated());

        return redirect()
            ->route('knowledge-base.index')
            ->with('status', __('Knowledge base updated successfully.'));
    }

    public function destroy(KnowledgeBaseArticle $knowledgeBaseArticle): RedirectResponse
    {
        $this->service->destroy($knowledgeBaseArticle);

        return redirect()
            ->route('knowledge-base.index')
            ->with('status', __('Knowledge base deleted successfully.'));
    }
}
