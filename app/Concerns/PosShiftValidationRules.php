<?php

namespace App\Concerns;

use App\Enums\PosShiftStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PosShiftValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function posShiftStoreRules(): array
    {
        return [
            'pos_register_id' => ['required', 'integer', 'exists:pos_registers,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'closing_amount' => ['prohibited'],
            'status' => ['nullable', 'string', Rule::in([PosShiftStatus::Open->value])],
            'opening_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function posShiftUpdateRules(): array
    {
        return [
            'pos_register_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'opening_amount' => ['prohibited'],
            'closing_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(array_column(PosShiftStatus::cases(), 'value'))],
            'opening_notes' => ['nullable', 'string', 'max:5000'],
            'closing_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
