<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\PosShiftValidationRules;
use App\Models\PosShift;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePosShiftRequest extends FormRequest
{
    use PosShiftValidationRules;

    public function authorize(): bool
    {
        $shift = $this->route('pos_shift');

        return $shift instanceof PosShift
            && ($this->user()?->can('update', $shift) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->posShiftUpdateRules();
    }
}
