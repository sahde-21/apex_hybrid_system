<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
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
}
