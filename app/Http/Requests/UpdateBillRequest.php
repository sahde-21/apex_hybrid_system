<?php

namespace App\Http\Requests;

use App\Enums\BillStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bills.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('bill')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('bills', 'reference_number')->ignore($id)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'],
            'status' => ['required', Rule::enum(BillStatus::class)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
