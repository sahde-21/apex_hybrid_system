<?php

namespace App\Concerns;

use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TicketValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ticketRules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('tickets', 'reference_number')],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ticketUpdateRules(?int $ticketId = null): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('tickets', 'reference_number')->ignore($ticketId)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
