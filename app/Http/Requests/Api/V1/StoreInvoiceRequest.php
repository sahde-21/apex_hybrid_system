<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('invoices.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('invoices', 'reference_number')],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'sale_order_id' => ['nullable', 'integer', 'exists:sale_orders,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->documentLineRules());
    }
}
