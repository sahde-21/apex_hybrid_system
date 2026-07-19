<?php

use App\Models\Branch;
use App\Models\PosRegister;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('POS Registers')] class extends Component {
    public string $name = '';
    public string $code = '';
    public ?int $warehouse_id = null;
    public ?int $branch_id = null;
    public bool $is_active = true;
    public bool $cash_drawer_enabled = true;
    public string $notes = '';

    #[Computed]
    public function registers()
    {
        return PosRegister::query()->with(['warehouse', 'branch'])->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->authorize('create', \App\Models\PosSale::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:pos_registers,code'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        PosRegister::query()->create([
            ...$validated,
            'is_active' => $this->is_active,
            'cash_drawer_enabled' => $this->cash_drawer_enabled,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->reset(['name', 'code', 'warehouse_id', 'branch_id', 'notes']);
        $this->is_active = true;
        $this->cash_drawer_enabled = true;
        unset($this->registers);

        Flux::toast(variant: 'success', text: __('Register created.'));
    }

    public function deleteRegister(int $id): void
    {
        abort_unless(auth()->user()?->can('pos.delete'), 403);
        PosRegister::query()->findOrFail($id)->delete();
        unset($this->registers);
        Flux::toast(variant: 'success', text: __('Register deleted.'));
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('scf.pos_registers')" :subtitle="__('Manage cash registers and drawer settings.')">
        <x-slot:actions>
            <flux:button :href="route('pos.terminal')" wire:navigate>{{ __('Open POS') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="grid max-w-2xl gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <flux:heading size="sm">{{ __('Add register') }}</flux:heading>
        <div class="grid gap-3 sm:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="code" :label="__('Code')" required />
            <flux:select wire:model="warehouse_id" :label="__('Warehouse')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->warehouses as $warehouse)
                    <flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="branch_id" :label="__('Branch')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->branches as $branch)
                    <flux:select.option :value="$branch->id">{{ $branch->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:textarea wire:model="notes" :label="__('Notes')" />
        <div class="flex gap-4">
            <flux:checkbox wire:model="is_active" :label="__('Active')" />
            <flux:checkbox wire:model="cash_drawer_enabled" :label="__('Cash drawer')" />
        </div>
        <flux:button type="submit" variant="primary">{{ __('Create register') }}</flux:button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-start dark:bg-zinc-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Name') }}</th>
                    <th class="px-3 py-2">{{ __('Code') }}</th>
                    <th class="px-3 py-2">{{ __('Warehouse') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                    <th class="px-3 py-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->registers as $register)
                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="px-3 py-2 font-medium">{{ $register->name }}</td>
                        <td class="px-3 py-2">{{ $register->code }}</td>
                        <td class="px-3 py-2">{{ $register->warehouse?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $register->is_active ? __('Active') : __('Inactive') }}</td>
                        <td class="px-3 py-2">
                            <flux:button size="sm" variant="danger" wire:click="deleteRegister({{ $register->id }})" wire:confirm="{{ __('Delete this register?') }}">
                                {{ __('Delete') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-zinc-500">{{ __('No registers yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
