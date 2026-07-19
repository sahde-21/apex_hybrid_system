<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gift-cards.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $initial = $this->input('initial_balance');

        if ($initial !== null) {
            $this->merge([
                'current_balance' => $initial,
            ]);
        }

        $this->request->remove('created_by');
        $this->request->remove('updated_by');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('gift_cards', 'code')],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
