<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment && $this->user()?->can('update', $payment);
    }

    public function rules(): array
    {
        $payment = $this->route('payment');

        return [
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('payments', 'reference_number')->ignore($payment?->id)],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'payment_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:100'],
            'account_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
