<?php

namespace App\Http\Requests;

use App\Enums\StockTransferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock-transfers.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('stock_transfers', 'reference_number')],
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer'],
            'transfer_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(StockTransferStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
