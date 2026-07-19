<?php

namespace App\Concerns;

use App\Enums\CrmInteractionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CrmInteractionValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function crmInteractionRules(?int $crmInteractionId = null): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'interaction_type' => ['required', Rule::enum(CrmInteractionType::class)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'interaction_date' => ['required', 'date'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:interaction_date'],
        ];
    }
}
