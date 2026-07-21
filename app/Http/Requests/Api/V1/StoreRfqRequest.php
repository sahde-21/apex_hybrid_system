<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfqRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('rfqs.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('rfqs', 'reference_number')],
            'purchase_request_id' => ['nullable', 'integer', 'exists:purchase_requests,id'],
            'rfq_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['integer', 'exists:contacts,id'],
        ], $this->documentLineRules());
    }
}
