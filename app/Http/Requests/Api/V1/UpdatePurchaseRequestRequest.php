<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequestRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        $purchaseRequest = $this->route('purchase_request');

        return $purchaseRequest && $this->user()?->can('update', $purchaseRequest);
    }

    public function rules(): array
    {
        $purchaseRequest = $this->route('purchase_request');

        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('purchase_requests', 'reference_number')->ignore($purchaseRequest?->id)],
            'requester_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'request_date' => ['sometimes', 'date'],
            'needed_by' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
