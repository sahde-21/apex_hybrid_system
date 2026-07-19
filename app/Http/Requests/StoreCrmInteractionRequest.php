<?php

namespace App\Http\Requests;

use App\Enums\CrmInteractionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmInteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm-interactions.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
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
