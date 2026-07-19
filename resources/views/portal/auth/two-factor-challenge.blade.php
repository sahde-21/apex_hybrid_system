<x-layouts.portal-auth>
    <div class="space-y-6">
        <div class="space-y-1 text-center">
            <flux:heading size="lg">{{ __('scf.portal.two_factor_title') }}</flux:heading>
            <flux:subheading>{{ __('scf.portal.two_factor_subtitle') }}</flux:subheading>
        </div>

        <form method="POST" action="{{ route('portal.two-factor.login.store') }}" class="space-y-4">
            @csrf
            <flux:input name="code" inputmode="numeric" autocomplete="one-time-code" :label="__('Authentication code')" autofocus />
            <flux:separator />
            <flux:input name="recovery_code" :label="__('Recovery code')" />
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Verify') }}</flux:button>
        </form>
    </div>
</x-layouts.portal-auth>
