<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('purchase-requests.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('purchase_requests', 'reference_number')],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'needed_by' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->documentLineRules());
    }
}
