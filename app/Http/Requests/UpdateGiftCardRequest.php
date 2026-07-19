<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gift-cards.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('created_by');
        $this->request->remove('updated_by');
        $this->request->remove('initial_balance');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('giftCard')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('gift_cards', 'code')->ignore($id)],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
