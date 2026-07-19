<x-layouts.portal-auth>
    <div class="space-y-6 text-center">
        <flux:heading size="lg">{{ __('Verify email') }}</flux:heading>
        <flux:subheading>{{ __('scf.portal.verify_subtitle') }}</flux:subheading>
        @if (session('status') == 'verification-link-sent')
            <flux:callout variant="success">{{ __('A new verification link has been sent.') }}</flux:callout>
        @endif
        <form method="POST" action="{{ route('portal.verification.send') }}">
            @csrf
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Resend verification email') }}</flux:button>
        </form>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">{{ __('scf.log_out') }}</flux:button>
        </form>
    </div>
</x-layouts.portal-auth>