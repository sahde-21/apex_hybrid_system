<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('bills.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('bills', 'reference_number')],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->documentLineRules());
    }
}
