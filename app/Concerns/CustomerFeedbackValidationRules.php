<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait CustomerFeedbackValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function customerFeedbackRules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'rating' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
            'feedback_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function customerFeedbackUpdateRules(?int $customerFeedbackId = null): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'rating' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
            'feedback_date' => ['required', 'date'],
        ];
    }
}
