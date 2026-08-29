<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-heading">{{ __('auth.confirm_password_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('auth.confirm_password_hint') }}</p>

    <form method="POST" action="{{ localized_route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-heading">{{ __('auth.password') }}</label>
            <input id="password" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('account.save') }}
        </button>
    </form>
</x-guest-layout>
