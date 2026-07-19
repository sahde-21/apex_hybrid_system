<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerformanceReviewRequest;
use App\Http\Requests\UpdatePerformanceReviewRequest;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Illuminate\Http\RedirectResponse;

class PerformanceReviewController extends Controller
{
    public function __construct(
        protected PerformanceReviewService $service,
    ) {}

    public function store(StorePerformanceReviewRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', __('Performance reviews created successfully.'));
    }

    public function update(UpdatePerformanceReviewRequest $request, PerformanceReview $performanceReview): RedirectResponse
    {
        $this->service->update($performanceReview, $request->validated());

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', __('Performance reviews updated successfully.'));
    }

    public function destroy(PerformanceReview $performanceReview): RedirectResponse
    {
        $this->service->destroy($performanceReview);

        return redirect()
            ->route('performance-reviews.index')
            ->with('status', __('Performance reviews deleted successfully.'));
    }
}
