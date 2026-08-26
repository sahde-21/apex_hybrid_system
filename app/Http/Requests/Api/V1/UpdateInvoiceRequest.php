<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    use DocumentLineRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice && $this->user()?->can('update', $invoice);
    }

    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('invoices', 'reference_number')->ignore($this->routeModelId('invoice'))],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'sale_order_id' => ['sometimes', 'nullable', 'integer', 'exists:sale_orders,id'],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
