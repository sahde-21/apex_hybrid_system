<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ChartOfAccountsService
{
    public function tree(): Collection
    {
        return Cache::remember('scf:accounting:coa-tree', config('accounting.cache_ttl', 120), function () {
            return Account::query()
                ->with(['children' => fn ($q) => $q->orderBy('code')])
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Account
    {
        $type = AccountType::from($data['type']);

        $account = Account::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'type' => $type,
            'normal_balance' => $data['normal_balance'] ?? $type->normalBalance()->value,
            'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
            'allow_manual_entry' => $data['allow_manual_entry'] ?? true,
            'description' => $data['description'] ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Cache::forget('scf:accounting:coa-tree');

        return $account;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, User $user, array $data): Account
    {
        if ($account->is_system && isset($data['code']) && $data['code'] !== $account->code) {
            throw ValidationException::withMessages([
                'code' => [__('scf.accounting_engine.system_account_protected')],
            ]);
        }

        $account->update([
            ...collect($data)->only([
                'code', 'name', 'parent_id', 'type', 'normal_balance', 'currency_code',
                'branch_id', 'is_active', 'allow_manual_entry', 'description',
            ])->all(),
            'updated_by' => $user->id,
        ]);

        Cache::forget('scf:accounting:coa-tree');

        return $account->refresh();
    }

    public function deactivate(Account $account, User $user): Account
    {
        if ($account->is_system) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.system_account_protected')],
            ]);
        }

        $account->update(['is_active' => false, 'updated_by' => $user->id]);
        Cache::forget('scf:accounting:coa-tree');

        return $account->refresh();
    }

    public function findSystem(string $key): Account
    {
        $account = Account::query()->where('system_key', $key)->where('is_active', true)->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.missing_system_account', ['key' => $key])],
            ]);
        }

        return $account;
    }

    public function systemId(string $configKey): int
    {
        $key = config('accounting.system_accounts.'.$configKey, $configKey);

        return $this->findSystem($key)->id;
    }
}
