<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\PosRegisterValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePosRegisterRequest extends FormRequest
{
    use PosRegisterValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('pos.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->posRegisterRules();
    }
}
