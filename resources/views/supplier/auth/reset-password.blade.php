<x-layouts.supplier-auth>
    <div class="space-y-6">
        <div class="space-y-1 text-center">
            <flux:heading size="lg">{{ __('Reset password') }}</flux:heading>
        </div>
        <form method="POST" action="{{ route('supplier.password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <flux:input name="email" type="email" :label="__('Email')" :value="old('email', $email)" required />
            <flux:input name="password" type="password" :label="__('Password')" required viewable />
            <flux:input name="password_confirmation" type="password" :label="__('Confirm password')" required viewable />
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Reset password') }}</flux:button>
        </form>
    </div>
</x-layouts.supplier-auth>
