<?php

use App\Services\Supplier\SupplierPortalService;
use App\Services\Supplier\SupplierTwoFactorService;
use Flux\Flux;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.supplier')] #[Title('Profile')] class extends Component {
    use WithFileUploads;

    public string $name = '';

    public string $company_name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $locale = 'en';

    public string $password = '';

    public string $password_confirmation = '';

    public $avatar = null;

    public string $twoFactorCode = '';

    public string $twoFactorQrSvg = '';

    /** @var list<string> */
    public array $recoveryCodes = [];

    public bool $showTwoFactorSetup = false;

    public function mount(): void
    {
        $supplier = auth('supplier')->user();
        $this->name = $supplier->name;
        $this->company_name = (string) ($supplier->contact?->company_name ?? '');
        $this->phone = (string) ($supplier->phone ?? '');
        $this->email = $supplier->email;
        $this->address = (string) ($supplier->contact?->address ?? '');
        $this->locale = $supplier->locale ?: 'en';
    }

    public function save(SupplierPortalService $portal): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'locale' => ['required', 'in:en,ar,ckb'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        if ($this->password !== '') {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $this->validate($rules);

        if ($this->password !== '') {
            $validated['password'] = $this->password;
        } else {
            unset($validated['password']);
        }

        $portal->updateProfile(auth('supplier')->user(), $validated, $this->avatar);
        session(['locale' => $this->locale]);
        app()->setLocale($this->locale);

        $this->reset('password', 'password_confirmation', 'avatar');
        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function enableTwoFactor(SupplierTwoFactorService $twoFactor): void
    {
        $setup = $twoFactor->beginSetup(auth('supplier')->user());
        $this->twoFactorQrSvg = $setup['qrSvg'];
        $this->recoveryCodes = $setup['recoveryCodes'];
        $this->showTwoFactorSetup = true;
        $this->twoFactorCode = '';
    }

    public function confirmTwoFactor(SupplierTwoFactorService $twoFactor): void
    {
        $this->validate(['twoFactorCode' => ['required', 'string']]);
        $twoFactor->confirm(auth('supplier')->user(), $this->twoFactorCode);
        $this->showTwoFactorSetup = false;
        $this->twoFactorCode = '';
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.two_factor_enabled'));
    }

    public function disableTwoFactor(SupplierTwoFactorService $twoFactor): void
    {
        $twoFactor->disable(auth('supplier')->user());
        $this->showTwoFactorSetup = false;
        $this->recoveryCodes = [];
        $this->twoFactorQrSvg = '';
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.two_factor_disabled'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass mx-auto max-w-2xl rounded-2xl p-6">
        <flux:heading size="lg">{{ __('scf.supplier_portal.profile') }}</flux:heading>
        <form wire:submit="save" class="mt-6 space-y-4">
            <flux:input wire:model="name" :label="__('Contact name')" required />
            <flux:input wire:model="company_name" :label="__('Company name')" />
            <flux:input wire:model="email" type="email" :label="__('Email')" required />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:textarea wire:model="address" :label="__('Address')" rows="3" />
            <flux:select wire:model="locale" :label="__('Language')">
                <option value="en">English</option>
                <option value="ar">العربية</option>
                <option value="ckb">کوردی</option>
            </flux:select>
            <div>
                <flux:label>{{ __('Profile photo') }}</flux:label>
                <input type="file" wire:model="avatar" accept="image/*" class="mt-2 block w-full text-sm" />
                @error('avatar') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <flux:separator />
            <flux:heading size="sm">{{ __('Change password') }}</flux:heading>
            <flux:input type="password" wire:model="password" :label="__('New password')" viewable />
            <flux:input type="password" wire:model="password_confirmation" :label="__('Confirm password')" viewable />
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </form>
    </div>

    <div class="portal-glass mx-auto max-w-2xl rounded-2xl p-6">
        <flux:heading size="sm">{{ __('scf.supplier_portal.two_factor') }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.two_factor_help') }}</flux:text>

        @if (auth('supplier')->user()->two_factor_enabled)
            <div class="mt-4 flex items-center justify-between gap-3">
                <flux:badge color="green">{{ __('Enabled') }}</flux:badge>
                <flux:button wire:click="disableTwoFactor" variant="danger" size="sm">{{ __('Disable') }}</flux:button>
            </div>
        @elseif ($showTwoFactorSetup)
            <div class="mt-4 space-y-4">
                <div class="flex justify-center [&_svg]:mx-auto">{!! $twoFactorQrSvg !!}</div>
                <div>
                    <flux:heading size="xs">{{ __('Recovery codes') }}</flux:heading>
                    <ul class="mt-2 grid gap-1 font-mono text-xs">
                        @foreach ($recoveryCodes as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
                <flux:input wire:model="twoFactorCode" inputmode="numeric" :label="__('Confirmation code')" />
                <flux:button wire:click="confirmTwoFactor" variant="primary">{{ __('Confirm & enable') }}</flux:button>
            </div>
        @else
            <div class="mt-4">
                <flux:button wire:click="enableTwoFactor" variant="filled" size="sm">{{ __('Enable two-factor authentication') }}</flux:button>
            </div>
        @endif
    </div>
</section>
