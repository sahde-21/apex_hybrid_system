<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVariantRequest;
use App\Http\Requests\UpdateVariantRequest;
use App\Models\Variant;
use App\Services\VariantService;
use Illuminate\Http\RedirectResponse;

class VariantController extends Controller
{
    public function __construct(
        protected VariantService $service,
    ) {}

    public function store(StoreVariantRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('variants.index')
            ->with('status', __('Variants created successfully.'));
    }

    public function update(UpdateVariantRequest $request, Variant $variant): RedirectResponse
    {
        $this->service->update($variant, $request->validated());

        return redirect()
            ->route('variants.index')
            ->with('status', __('Variants updated successfully.'));
    }

    public function destroy(Variant $variant): RedirectResponse
    {
        $this->service->destroy($variant);

        return redirect()
            ->route('variants.index')
            ->with('status', __('Variants deleted successfully.'));
    }
}
