<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchase_order');

        return $purchaseOrder && $this->user()?->can('update', $purchaseOrder);
    }

    public function rules(): array
    {
        $purchaseOrder = $this->route('purchase_order');

        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('purchase_orders', 'reference_number')->ignore($purchaseOrder?->id)],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'rfq_id' => ['sometimes', 'nullable', 'integer', 'exists:rfqs,id'],
            'purchase_request_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_requests,id'],
            'order_date' => ['sometimes', 'date'],
            'expected_date' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
