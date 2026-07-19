<?php

use App\Models\User;
use App\Services\UserService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Title('Edit user')] class extends Component {
    use WithFileUploads;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    /** @var list<string> */
    public array $roles = [];

    /** @var list<string> */
    public array $permissions = [];

    public $avatar = null;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->is_active = (bool) $user->is_active;
        $this->roles = $user->getRoleNames()->values()->all();
        $this->permissions = $user->getPermissionNames()->values()->all();
    }

    #[Computed]
    public function availableRoles()
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name');
    }

    #[Computed]
    public function availablePermissions()
    {
        return Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name');
    }

    public function save(UserService $users): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $this->authorize('update', $this->user);

        $canAssign = auth()->user()->can('assignRoles', $this->user);

        $users->updateUser(
            $this->user,
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'],
            ],
            $canAssign ? ($validated['roles'] ?? []) : null,
            $canAssign ? ($validated['permissions'] ?? []) : null,
            $this->avatar,
        );

        if (! empty($validated['password'])) {
            $this->authorize('changePassword', $this->user);
            $users->changePassword($this->user, $validated['password']);
        }

        Flux::toast(variant: 'success', text: __('User updated successfully.'));
        $this->redirect(route('users.show', $this->user), navigate: true);
    }
}; ?>

<section class="scf-page max-w-3xl">
    <div>
        <flux:heading size="xl">{{ __('Edit user') }}</flux:heading>
        <flux:subheading>{{ $user->email }}</flux:subheading>
    </div>

    <form wire:submit="save" class="scf-card mt-6 grid gap-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="email" type="email" :label="__('Email address')" required />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <div class="flex items-end">
                <flux:switch wire:model="is_active" :label="__('Active')" />
            </div>
            <flux:input wire:model="password" type="password" :label="__('New password')" viewable />
            <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm password')" viewable />
        </div>

        @if ($user->avatarUrl())
            <div class="flex items-center gap-3">
                <img src="{{ $user->avatarUrl() }}" alt="" class="size-14 rounded-full object-cover">
                <flux:text>{{ __('Current avatar') }}</flux:text>
            </div>
        @endif

        <flux:input wire:model="avatar" type="file" :label="__('Avatar')" accept="image/*" />

        <div>
            <flux:heading size="sm" class="mb-3">{{ __('Assign roles') }}</flux:heading>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($this->availableRoles as $role)
                    <flux:checkbox wire:model="roles" value="{{ $role }}" :label="$role" />
                @endforeach
            </div>
        </div>

        <div>
            <flux:heading size="sm" class="mb-3">{{ __('Direct permissions') }}</flux:heading>
            <div class="max-h-56 overflow-y-auto rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($this->availablePermissions as $permission)
                        <flux:checkbox wire:model="permissions" value="{{ $permission }}" :label="$permission" />
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <flux:button :href="route('users.show', $user)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
