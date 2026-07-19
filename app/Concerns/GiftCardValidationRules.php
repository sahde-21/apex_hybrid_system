<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait GiftCardValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function giftCardRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('gift_cards', 'code')],
            'initial_balance' => ['required', 'numeric'],
            'current_balance' => ['required', 'numeric'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function giftCardUpdateRules(?int $giftCardId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('gift_cards', 'code')->ignore($giftCardId)],
            'initial_balance' => ['required', 'numeric'],
            'current_balance' => ['required', 'numeric'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
