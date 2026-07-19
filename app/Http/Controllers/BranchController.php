<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $service,
    ) {}

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('status', __('Branches created successfully.'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->service->update($branch, $request->validated());

        return redirect()
            ->route('branches.index')
            ->with('status', __('Branches updated successfully.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->service->destroy($branch);

        return redirect()
            ->route('branches.index')
            ->with('status', __('Branches deleted successfully.'));
    }
}
