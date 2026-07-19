<x-layouts.supplier-auth>
    <div class="space-y-6">
        <div class="space-y-1 text-center">
            <flux:heading size="lg">{{ __('scf.supplier_portal.login_title') }}</flux:heading>
            <flux:subheading>{{ __('scf.supplier_portal.login_subtitle') }}</flux:subheading>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <form method="POST" action="{{ route('supplier.login.store') }}" class="space-y-4">
            @csrf
            <flux:input name="email" type="email" :label="__('Email')" :value="old('email')" required autofocus />
            <flux:input name="password" type="password" :label="__('Password')" required viewable />
            <div class="flex items-center justify-between gap-3">
                <flux:checkbox name="remember" :label="__('Remember me')" />
                <flux:link :href="route('supplier.password.request')" class="text-sm">{{ __('Forgot password?') }}</flux:link>
            </div>
            <flux:button type="submit" variant="primary" class="w-full">{{ __('scf.supplier_portal.sign_in') }}</flux:button>
        </form>
    </div>
</x-layouts.supplier-auth>
