<?php

use App\Services\Portal\PortalService;
use Flux\Flux;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.portal')] #[Title('Profile')] class extends Component {
    use WithFileUploads;

    public string $name = '';

    public string $phone = '';

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
        $customer = auth('portal')->user();
        $this->name = $customer->name;
        $this->phone = (string) ($customer->phone ?? '');
        $this->address = (string) ($customer->contact?->address ?? '');
        $this->locale = $customer->locale ?: 'en';
    }

    public function save(PortalService $portal): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
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

        $portal->updateProfile(auth('portal')->user(), $validated, $this->avatar);
        session(['locale' => $this->locale]);
        app()->setLocale($this->locale);

        $this->reset('password', 'password_confirmation', 'avatar');
        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function enableTwoFactor(\App\Services\Portal\PortalTwoFactorService $twoFactor): void
    {
        $setup = $twoFactor->beginSetup(auth('portal')->user());
        $this->twoFactorQrSvg = $setup['qrSvg'];
        $this->recoveryCodes = $setup['recoveryCodes'];
        $this->showTwoFactorSetup = true;
        $this->twoFactorCode = '';
    }

    public function confirmTwoFactor(\App\Services\Portal\PortalTwoFactorService $twoFactor): void
    {
        $this->validate(['twoFactorCode' => ['required', 'string']]);
        $twoFactor->confirm(auth('portal')->user(), $this->twoFactorCode);
        $this->showTwoFactorSetup = false;
        $this->twoFactorCode = '';
        Flux::toast(variant: 'success', text: __('scf.portal.two_factor_enabled'));
    }

    public function disableTwoFactor(\App\Services\Portal\PortalTwoFactorService $twoFactor): void
    {
        $twoFactor->disable(auth('portal')->user());
        $this->showTwoFactorSetup = false;
        $this->recoveryCodes = [];
        $this->twoFactorQrSvg = '';
        Flux::toast(variant: 'success', text: __('scf.portal.two_factor_disabled'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass mx-auto max-w-2xl rounded-2xl p-6">
        <flux:heading size="lg">{{ __('scf.portal.profile') }}</flux:heading>
        <form wire:submit="save" class="mt-6 space-y-4">
            <flux:input wire:model="name" :label="__('Name')" required />
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
        <flux:heading size="sm">{{ __('scf.portal.two_factor') }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('scf.portal.two_factor_help') }}</flux:text>

        @if (auth('portal')->user()->two_factor_enabled)
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
