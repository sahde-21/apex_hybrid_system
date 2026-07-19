<?php

use App\Models\User;
use App\Services\UserService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Users')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $role = '';

    #[Url]
    public bool $trashed = false;

    /** @var list<int> */
    public array $selected = [];

    public string $bulkAction = '';

    public ?int $userToDelete = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true)->whereNull('locked_at'))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->status === 'locked', fn ($q) => $q->whereNotNull('locked_at'))
            ->when($this->role, fn ($q) => $q->role($this->role))
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedTrashed(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function confirmDelete(int $id): void
    {
        $this->userToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteUser(UserService $users): void
    {
        if ($this->userToDelete === null) {
            return;
        }

        $user = User::query()->findOrFail($this->userToDelete);
        $this->authorize('delete', $user);
        $users->softDelete($user);

        $this->userToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('User deleted successfully.'));
    }

    public function restoreUser(int $id, UserService $users): void
    {
        $user = User::query()->onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $user);
        $users->restore($user);
        Flux::toast(variant: 'success', text: __('User restored successfully.'));
    }

    public function toggleLock(int $id, UserService $users): void
    {
        $user = User::query()->findOrFail($id);

        if ($user->isLocked()) {
            $this->authorize('unlock', $user);
            $users->unlock($user);
            Flux::toast(variant: 'success', text: __('User unlocked successfully.'));

            return;
        }

        $this->authorize('lock', $user);
        $users->lock($user);
        Flux::toast(variant: 'success', text: __('User locked successfully.'));
    }

    public function toggleActive(int $id, UserService $users): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);

        if ($user->is_active) {
            $users->deactivate($user);
            Flux::toast(variant: 'success', text: __('User deactivated successfully.'));
        } else {
            $users->activate($user);
            Flux::toast(variant: 'success', text: __('User activated successfully.'));
        }
    }

    public function forcePasswordReset(int $id, UserService $users): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('forcePasswordReset', $user);
        $users->forcePasswordReset($user);
        Flux::toast(variant: 'success', text: __('Password reset email sent.'));
    }

    public function applyBulk(UserService $users): void
    {
        $this->validate([
            'bulkAction' => 'required|in:activate,deactivate,lock,unlock,delete,restore',
            'selected' => 'required|array|min:1',
        ]);

        if (in_array($this->bulkAction, ['delete', 'restore', 'lock', 'unlock', 'activate', 'deactivate'], true)) {
            $this->authorize('viewAny', User::class);
        }

        $count = $users->bulkAction($this->bulkAction, $this->selected, Auth::user());
        $this->selected = [];
        $this->bulkAction = '';

        Flux::toast(variant: 'success', text: __('Bulk action completed for :count users.', ['count' => $count]));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Users')"
        :subtitle="__('Enterprise user management, roles, and access control')"
    >
        <x-slot:actions>
            @can('create', App\Models\User::class)
            <flux:button :href="route('users.create')" icon="plus" variant="primary" wire:navigate>
                {{ __('Create user') }}
            </flux:button>
        @endcan
        </x-slot:actions>
    </x-page-header>

    <x-module-toolbar>
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name, email, or phone...')" />
        </x-slot:search>
        <x-slot:filters>
            <flux:select class="min-w-36" wire:model.live="status" :placeholder="__('All statuses')">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                <flux:select.option value="locked">{{ __('Locked') }}</flux:select.option>
            </flux:select>
            <flux:select class="min-w-40" wire:model.live="role" :placeholder="__('All roles')">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach ($this->roles as $roleName)
                    <flux:select.option :value="$roleName">{{ $roleName }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:checkbox wire:model.live="trashed" :label="__('Show deleted')" />
        </x-slot:filters>
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <flux:select class="min-w-40" wire:model="bulkAction" :placeholder="__('Bulk actions')">
                    <flux:select.option value="">{{ __('Bulk actions') }}</flux:select.option>
                    <flux:select.option value="activate">{{ __('Activate') }}</flux:select.option>
                    <flux:select.option value="deactivate">{{ __('Deactivate') }}</flux:select.option>
                    <flux:select.option value="lock">{{ __('Lock') }}</flux:select.option>
                    <flux:select.option value="unlock">{{ __('Unlock') }}</flux:select.option>
                    <flux:select.option value="delete">{{ __('Delete') }}</flux:select.option>
                    <flux:select.option value="restore">{{ __('Restore') }}</flux:select.option>
                </flux:select>
                <flux:button size="sm" variant="filled" wire:click="applyBulk" :disabled="empty($selected) || $bulkAction === ''">
                    {{ __('Apply') }}
                </flux:button>
            </div>
        </x-slot:actions>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column></flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Last login') }}</flux:table.column>
                <flux:table.column>{{ __('Last activity') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell>
                            <flux:checkbox wire:model="selected" value="{{ $user->id }}" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                @if ($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="size-9 rounded-full object-cover">
                                @else
                                    <div class="flex size-9 items-center justify-center rounded-full bg-sky-500/15 text-xs font-semibold text-sky-600 dark:text-sky-300">
                                        {{ $user->initials() }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                                @empty
                                    <span class="text-xs text-zinc-500">—</span>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($user->trashed())
                                <flux:badge size="sm" color="red">{{ __('Deleted') }}</flux:badge>
                            @elseif ($user->isLocked())
                                <flux:badge size="sm" color="amber">{{ __('Locked') }}</flux:badge>
                            @elseif ($user->is_active)
                                <flux:badge size="sm" color="green">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-500">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-500">{{ $user->last_activity_at?->diffForHumans() ?? '—' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="sm" variant="ghost" :href="route('users.show', $user)" wire:navigate icon="eye" />
                                @unless ($user->trashed())
                                    @can('update', $user)
                                        <flux:button size="sm" variant="ghost" :href="route('users.edit', $user)" wire:navigate icon="pencil-square" />
                                    @endcan
                                    @can('lock', $user)
                                        <flux:button size="sm" variant="ghost" wire:click="toggleLock({{ $user->id }})" :icon="$user->isLocked() ? 'lock-open' : 'lock-closed'" />
                                    @endcan
                                    @can('update', $user)
                                        <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $user->id }})" icon="power" />
                                    @endcan
                                    @can('forcePasswordReset', $user)
                                        <flux:button size="sm" variant="ghost" wire:click="forcePasswordReset({{ $user->id }})" icon="key" />
                                    @endcan
                                    @can('delete', $user)
                                        <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $user->id }})" icon="trash" />
                                    @endcan
                                @else
                                    @can('restore', $user)
                                        <flux:button size="sm" variant="primary" wire:click="restoreUser({{ $user->id }})">{{ __('Restore') }}</flux:button>
                                    @endcan
                                @endunless
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No users found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this user? This action can be restored later.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteUser">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
