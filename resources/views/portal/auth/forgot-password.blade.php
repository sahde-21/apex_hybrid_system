<x-layouts.portal-auth>
    <div class="space-y-6">
        <div class="space-y-1 text-center">
            <flux:heading size="lg">{{ __('Forgot password') }}</flux:heading>
            <flux:subheading>{{ __('scf.portal.forgot_subtitle') }}</flux:subheading>
        </div>
        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif
        <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-4">
            @csrf
            <flux:input name="email" type="email" :label="__('Email')" :value="old('email')" required autofocus />
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Email password reset link') }}</flux:button>
            <div class="text-center">
                <flux:link :href="route('portal.login')">{{ __('scf.portal.back_to_login') }}</flux:link>
            </div>
        </form>
    </div>
</x-layouts.portal-auth>