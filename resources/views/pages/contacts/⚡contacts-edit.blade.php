<?php

use App\Concerns\ContactValidationRules;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit contact')] class extends Component {
    use ContactValidationRules;

    public Contact $contact;

    public string $name = '';
    public string $type = 'customer';
    public string $company_name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $tax_number = '';
    public string $opening_balance = '0';

    public function mount(Contact $contact): void
    {
        $this->contact = $contact;
        $this->name = $contact->name;
        $this->type = $contact->type->value;
        $this->company_name = $contact->company_name ?? '';
        $this->email = $contact->email ?? '';
        $this->phone = $contact->phone ?? '';
        $this->address = $contact->address ?? '';
        $this->tax_number = $contact->tax_number ?? '';
        $this->opening_balance = (string) $contact->opening_balance;
    }

    public function save(): void
    {
        $validated = $this->validate($this->contactRules($this->contact->id));

        $this->contact->update($validated);

        Flux::toast(variant: 'success', text: __('Contact updated successfully.'));

        $this->redirect(route('contacts.index'), navigate: true);
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Edit contact')"
        :subtitle="__('Update customer or supplier details')"
    />

    @include('contacts.partials.form', [
        'submitAction' => 'save',
        'submitLabel' => __('Save changes'),
    ])
</section>
