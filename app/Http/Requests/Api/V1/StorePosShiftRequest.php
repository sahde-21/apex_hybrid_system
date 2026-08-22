<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\PosShiftValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePosShiftRequest extends FormRequest
{
    use PosShiftValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('pos.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->posShiftStoreRules();
    }
}
