<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') || $this->user()?->can('payments.record');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('payments', 'reference_number')],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id', 'required_without:bill_id'],
            'bill_id' => ['nullable', 'integer', 'exists:bills,id', 'required_without:invoice_id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['nullable', Rule::enum(PaymentType::class)],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'account_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
