<?php

namespace App\Concerns;

use App\Enums\SubscriptionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait SubscriptionValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function subscriptionRules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'plan_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric'],
            'billing_cycle' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function subscriptionUpdateRules(?int $subscriptionId = null): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'plan_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric'],
            'billing_cycle' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ];
    }
}
