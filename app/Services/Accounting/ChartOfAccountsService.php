<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ChartOfAccountsService
{
    public function __construct(
        protected AccountingAuditService $audit,
    ) {}

    public function tree(bool $includeArchived = false): Collection
    {
        $cacheKey = 'scf:accounting:coa-tree'.($includeArchived ? ':archived' : '');

        return Cache::remember($cacheKey, config('accounting.cache_ttl', 120), function () use ($includeArchived) {
            $query = Account::query()
                ->with(['children' => fn ($q) => $q->orderBy('code')])
                ->whereNull('parent_id')
                ->orderBy('code');

            if ($includeArchived) {
                $query->withTrashed();
            }

            return $query->get();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Account
    {
        $type = AccountType::from($data['type']);
        $this->assertValidParent($data['parent_id'] ?? null);

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
            'opening_balance' => $data['opening_balance'] ?? 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->forgetTreeCache();
        $this->audit->log('account.created', $account, $user);

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

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $account->id) {
            throw ValidationException::withMessages([
                'parent_id' => [__('scf.accounting_engine.account_cannot_be_own_parent')],
            ]);
        }

        $this->assertValidParent($data['parent_id'] ?? null, $account->id);

        $account->update([
            ...collect($data)->only([
                'code', 'name', 'parent_id', 'type', 'normal_balance', 'currency_code',
                'branch_id', 'is_active', 'allow_manual_entry', 'description', 'opening_balance',
            ])->all(),
            'updated_by' => $user->id,
        ]);

        $this->forgetTreeCache();
        $this->audit->log('account.updated', $account, $user);

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
        $this->forgetTreeCache();
        $this->audit->log('account.deactivated', $account, $user);

        return $account->refresh();
    }

    public function activate(Account $account, User $user): Account
    {
        $account->update(['is_active' => true, 'updated_by' => $user->id]);
        $this->forgetTreeCache();
        $this->audit->log('account.activated', $account, $user);

        return $account->refresh();
    }

    public function archive(Account $account, User $user): Account
    {
        if ($account->is_system) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.system_account_protected')],
            ]);
        }

        $account->update(['is_active' => false, 'updated_by' => $user->id]);
        $account->delete();
        $this->forgetTreeCache();
        $this->audit->log('account.archived', $account, $user);

        return $account;
    }

    public function restore(Account $account, User $user): Account
    {
        if (! $account->trashed()) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.account_not_archived')],
            ]);
        }

        $account->restore();
        $account->update(['is_active' => true, 'updated_by' => $user->id]);
        $this->forgetTreeCache();
        $this->audit->log('account.restored', $account, $user);

        return $account->refresh();
    }

    public function delete(Account $account, User $user): void
    {
        if ($account->is_system) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.system_account_protected')],
            ]);
        }

        if ($account->children()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.account_has_children')],
            ]);
        }

        if ($account->lines()->exists()) {
            throw ValidationException::withMessages([
                'account' => [__('scf.accounting_engine.account_has_journal_lines')],
            ]);
        }

        $this->audit->log('account.deleted', $account, $user, [
            'code' => $account->code,
            'name' => $account->name,
        ]);

        $account->forceDelete();
        $this->forgetTreeCache();
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

    protected function forgetTreeCache(): void
    {
        Cache::forget('scf:accounting:coa-tree');
        Cache::forget('scf:accounting:coa-tree:archived');
    }

    protected function assertValidParent(mixed $parentId, ?int $selfId = null): void
    {
        if ($parentId === null || $parentId === '' || $parentId === 0) {
            return;
        }

        $parent = Account::query()->find($parentId);

        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => [__('scf.accounting_engine.invalid_parent_account')],
            ]);
        }

        if ($selfId !== null && $this->isDescendantOf($parent, $selfId)) {
            throw ValidationException::withMessages([
                'parent_id' => [__('scf.accounting_engine.account_circular_parent')],
            ]);
        }
    }

    protected function isDescendantOf(Account $candidate, int $ancestorId): bool
    {
        $current = $candidate;

        while ($current->parent_id !== null) {
            if ((int) $current->parent_id === $ancestorId) {
                return true;
            }

            $current = $current->parent;
            if ($current === null) {
                break;
            }
        }

        return false;
    }
}
