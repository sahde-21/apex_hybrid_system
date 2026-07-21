<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('purchase-orders.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('purchase_orders', 'reference_number')],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'rfq_id' => ['nullable', 'integer', 'exists:rfqs,id'],
            'purchase_request_id' => ['nullable', 'integer', 'exists:purchase_requests,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->documentLineRules());
    }
}
