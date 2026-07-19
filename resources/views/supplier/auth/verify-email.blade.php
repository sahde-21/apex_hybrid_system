<x-layouts.supplier-auth>
    <div class="space-y-6">
        <div class="space-y-1 text-center">
            <flux:heading size="lg">{{ __('Verify email') }}</flux:heading>
            <flux:subheading>{{ __('scf.supplier_portal.verify_subtitle') }}</flux:subheading>
        </div>
        @if (session('status') === 'verification-link-sent')
            <flux:callout variant="success">{{ __('A new verification link has been sent.') }}</flux:callout>
        @endif
        <form method="POST" action="{{ route('supplier.verification.send') }}">
            @csrf
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Resend verification email') }}</flux:button>
        </form>
        <form method="POST" action="{{ route('supplier.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">{{ __('Log out') }}</flux:button>
        </form>
    </div>
</x-layouts.supplier-auth>
