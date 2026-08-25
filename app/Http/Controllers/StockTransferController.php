<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Inventory\StockTransferWorkflowService;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $service,
        protected StockTransferWorkflowService $workflow,
    ) {}

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->workflow->createDraft([
            'reference_number' => (string) $request->validated('reference_number'),
            'product_id' => (int) $request->validated('product_id'),
            'variant_id' => $request->validated('variant_id') !== null ? (int) $request->validated('variant_id') : null,
            'from_warehouse_id' => (int) $request->validated('from_warehouse_id'),
            'to_warehouse_id' => (int) $request->validated('to_warehouse_id'),
            'quantity' => (int) $request->validated('quantity'),
            'transfer_date' => (string) $request->validated('transfer_date'),
            'notes' => $request->validated('notes') !== null ? (string) $request->validated('notes') : null,
        ], $user);

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers created successfully.'));
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('update', $stockTransfer);

        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->workflow->updateDraft($stockTransfer, [
            'reference_number' => (string) $request->validated('reference_number'),
            'product_id' => (int) $request->validated('product_id'),
            'variant_id' => $request->validated('variant_id') !== null ? (int) $request->validated('variant_id') : null,
            'from_warehouse_id' => (int) $request->validated('from_warehouse_id'),
            'to_warehouse_id' => (int) $request->validated('to_warehouse_id'),
            'quantity' => (int) $request->validated('quantity'),
            'transfer_date' => (string) $request->validated('transfer_date'),
            'notes' => $request->validated('notes') !== null ? (string) $request->validated('notes') : null,
        ], $user);

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers updated successfully.'));
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('delete', $stockTransfer);
        $this->service->destroy($stockTransfer);

        return redirect()
            ->route('stock-transfers.index')
            ->with('status', __('Stock transfers deleted successfully.'));
    }
}
