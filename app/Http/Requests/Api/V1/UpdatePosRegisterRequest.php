<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\PosRegisterValidationRules;
use App\Models\PosRegister;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePosRegisterRequest extends FormRequest
{
    use PosRegisterValidationRules;

    public function authorize(): bool
    {
        $register = $this->route('pos_register');

        return $register instanceof PosRegister
            && ($this->user()?->can('update', $register) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $register = $this->route('pos_register');

        return $this->posRegisterRules($register instanceof PosRegister ? $register->id : null);
    }
}
