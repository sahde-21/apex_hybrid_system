<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillOfMaterialRequest;
use App\Http\Requests\UpdateBillOfMaterialRequest;
use App\Models\BillOfMaterial;
use App\Services\BillOfMaterialService;
use Illuminate\Http\RedirectResponse;

class BillOfMaterialController extends Controller
{
    public function __construct(
        protected BillOfMaterialService $service,
    ) {}

    public function store(StoreBillOfMaterialRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('bill-of-materials.index')
            ->with('status', __('Bill of materials created successfully.'));
    }

    public function update(UpdateBillOfMaterialRequest $request, BillOfMaterial $billOfMaterial): RedirectResponse
    {
        $this->service->update($billOfMaterial, $request->validated());

        return redirect()
            ->route('bill-of-materials.index')
            ->with('status', __('Bill of materials updated successfully.'));
    }

    public function destroy(BillOfMaterial $billOfMaterial): RedirectResponse
    {
        $this->service->destroy($billOfMaterial);

        return redirect()
            ->route('bill-of-materials.index')
            ->with('status', __('Bill of materials deleted successfully.'));
    }
}
