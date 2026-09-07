<?php

use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Create contact')] class extends \App\Livewire\ConcernBases\ContactValidationRulesBase {

    public string $name = '';
    public string $type = 'customer';
    public string $company_name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $tax_number = '';
    public string $opening_balance = '0';

    public function save(): void
    {
        $validated = $this->validate($this->contactRules());

        Contact::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Contact created successfully.'));

        $this->redirect(route('contacts.index'), navigate: true);
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Create contact')"
        :subtitle="__('Add a new customer or supplier')"
    />

    @include('contacts.partials.form', [
        'submitAction' => 'save',
        'submitLabel' => __('Create contact'),
    ])
</section>
