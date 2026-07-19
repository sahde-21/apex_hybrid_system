<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualityControlRequest;
use App\Http\Requests\UpdateQualityControlRequest;
use App\Models\QualityControl;
use App\Services\QualityControlService;
use Illuminate\Http\RedirectResponse;

class QualityControlController extends Controller
{
    public function __construct(
        protected QualityControlService $service,
    ) {}

    public function store(StoreQualityControlRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('quality-controls.index')
            ->with('status', __('Quality control created successfully.'));
    }

    public function update(UpdateQualityControlRequest $request, QualityControl $qualityControl): RedirectResponse
    {
        $this->service->update($qualityControl, $request->validated());

        return redirect()
            ->route('quality-controls.index')
            ->with('status', __('Quality control updated successfully.'));
    }

    public function destroy(QualityControl $qualityControl): RedirectResponse
    {
        $this->service->destroy($qualityControl);

        return redirect()
            ->route('quality-controls.index')
            ->with('status', __('Quality control deleted successfully.'));
    }
}
