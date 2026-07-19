<?php

use App\Concerns\ContactValidationRules;
use App\Enums\ContactType;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Contacts')] class extends Component {
    use ContactValidationRules;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    public ?int $contactToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('company_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('tax_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $contactId): void
    {
        $this->contactToDelete = $contactId;
        $this->showDeleteModal = true;
    }

    public function deleteContact(): void
    {
        if ($this->contactToDelete === null) {
            return;
        }

        $model = Contact::query()->findOrFail($this->contactToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->contactToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Contact deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Contacts')"
        :subtitle="__('Manage customers and suppliers across your business')"
    />

    <x-module-toolbar
        export-type="contacts"
        create-permission="contacts.create"
        :create-route="route('contacts.create')"
        :create-label="__('Add contact')"
    >
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name, company, email, phone, or tax number...')" />
        </x-slot:search>
        <x-slot:filters>
            <flux:select class="min-w-40" wire:model.live="type" :placeholder="__('All types')">
                <flux:select.option value="">{{ __('All types') }}</flux:select.option>
                @foreach (ContactType::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-slot:filters>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->contacts">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Company') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Tax number') }}</flux:table.column>
                <flux:table.column>{{ __('Opening balance') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->contacts as $contact)
                    <flux:table.row wire:key="contact-{{ $contact->id }}">
                        <flux:table.cell class="font-medium">{{ $contact->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$contact->type->color()">{{ $contact->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $contact->company_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $contact->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $contact->phone ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $contact->tax_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <span>{{ number_format(abs((float) $contact->opening_balance), 2) }}</span>
                                <flux:badge size="sm" :color="$contact->balanceColor()">{{ $contact->balanceLabel() }}</flux:badge>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $contact)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('contacts.edit', $contact)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $contact)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $contact->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <x-empty-state icon="inbox" :title="__('No contacts found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete contact') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this contact? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteContact">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
