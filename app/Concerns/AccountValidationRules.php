<?php

namespace App\Concerns;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AccountValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string|callable>>
     */
    protected function accountRules(?int $accountId = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('accounts', 'code')->ignore($accountId),
            ],
            'name' => ['required', 'string', 'max:180'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, \Closure $fail) use ($accountId): void {
                    if ($accountId !== null && $value !== null && (int) $value === $accountId) {
                        $fail(__('scf.accounting_engine.account_cannot_be_own_parent'));
                    }
                },
            ],
            'type' => ['required', 'string', Rule::in(array_column(AccountType::cases(), 'value'))],
            'normal_balance' => ['required', 'string', Rule::in(array_column(NormalBalance::cases(), 'value'))],
            'currency_code' => ['required', 'string', 'size:3'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'is_active' => ['required', 'boolean'],
            'allow_manual_entry' => ['required', 'boolean'],
            'opening_balance' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
