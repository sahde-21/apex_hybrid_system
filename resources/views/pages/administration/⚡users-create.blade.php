<?php

use App\Models\User;
use App\Services\UserService;
use Flux\Flux;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Title('Create user')] class extends Component {
    use WithFileUploads;

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

    public function mount(): void
    {
        $this->authorize('create', User::class);
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $users->createUser(
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'is_active' => $validated['is_active'],
            ],
            $validated['roles'] ?? [],
            $validated['permissions'] ?? [],
            $this->avatar,
        );

        Flux::toast(variant: 'success', text: __('User created successfully.'));
        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<section class="scf-page max-w-3xl">
    <div>
        <flux:heading size="xl">{{ __('Create user') }}</flux:heading>
        <flux:subheading>{{ __('Invite a teammate and assign roles & permissions') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="scf-card mt-6 grid gap-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="email" type="email" :label="__('Email address')" required />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <div class="flex items-end">
                <flux:switch wire:model="is_active" :label="__('Active')" />
            </div>
            <flux:input wire:model="password" type="password" :label="__('Password')" required viewable />
            <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm password')" required viewable />
        </div>

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
            <flux:button type="submit" variant="primary">{{ __('Create user') }}</flux:button>
            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
