<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loyalty-programs.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('loyalty_programs', 'code')],
            'points_per_currency' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
