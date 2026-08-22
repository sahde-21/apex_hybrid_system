<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\AccountValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('chart-of-accounts.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->accountRules();
    }
}
