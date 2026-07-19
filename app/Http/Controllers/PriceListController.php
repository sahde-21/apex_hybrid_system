<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePriceListRequest;
use App\Http\Requests\UpdatePriceListRequest;
use App\Models\PriceList;
use App\Services\PriceListService;
use Illuminate\Http\RedirectResponse;

class PriceListController extends Controller
{
    public function __construct(
        protected PriceListService $service,
    ) {}

    public function store(StorePriceListRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('price-lists.index')
            ->with('status', __('Price lists created successfully.'));
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList): RedirectResponse
    {
        $this->service->update($priceList, $request->validated());

        return redirect()
            ->route('price-lists.index')
            ->with('status', __('Price lists updated successfully.'));
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        $this->service->destroy($priceList);

        return redirect()
            ->route('price-lists.index')
            ->with('status', __('Price lists deleted successfully.'));
    }
}
