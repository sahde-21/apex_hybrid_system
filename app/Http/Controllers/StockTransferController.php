<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $service,
    ) {}

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers created successfully.'));
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->update($stockTransfer, $request->validated());

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers updated successfully.'));
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->service->destroy($stockTransfer);

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers deleted successfully.'));
    }
}
