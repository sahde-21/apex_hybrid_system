<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFixedAssetRequest;
use App\Http\Requests\UpdateFixedAssetRequest;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Illuminate\Http\RedirectResponse;

class FixedAssetController extends Controller
{
    public function __construct(
        protected FixedAssetService $service,
    ) {}

    public function store(StoreFixedAssetRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('fixed-assets.index')
            ->with('status', __('Fixed assets created successfully.'));
    }

    public function update(UpdateFixedAssetRequest $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $this->service->update($fixedAsset, $request->validated());

        return redirect()
            ->route('fixed-assets.index')
            ->with('status', __('Fixed assets updated successfully.'));
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        $this->service->destroy($fixedAsset);

        return redirect()
            ->route('fixed-assets.index')
            ->with('status', __('Fixed assets deleted successfully.'));
    }
}
