<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('tickets.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('ticket');

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('tickets', 'reference_number')->ignore($id)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
