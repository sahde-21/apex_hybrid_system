<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\AccountValidationRules;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof Account
            && ($this->user()?->can('update', $account) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $account = $this->route('account');

        return $this->accountRules($account instanceof Account ? $account->id : null);
    }
}
