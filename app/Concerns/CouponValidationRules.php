<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CouponValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function couponRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('coupons', 'code')],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['required', 'numeric'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function couponUpdateRules(?int $couponId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('coupons', 'code')->ignore($couponId)],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['required', 'numeric'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
