<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-heading">{{ __('auth.login_heading') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('auth.login_sub_intro') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ localized_route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-heading">{{ __('auth.email') }}</label>
            <input id="email" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="email" name="email" :value="old('email')" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-heading">{{ __('auth.password') }}</label>
            <input id="password" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-border text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-sm text-text">{{ __('auth.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-primary hover:underline" href="{{ localized_route('password.request') }}">
                    {{ __('auth.forgot_password') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('auth.login') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-text-muted">
        {{ __('auth.no_account') }}
        <a href="{{ localized_route('register') }}" class="font-medium text-primary hover:underline">{{ __('auth.create_account') }}</a>
    </p>
</x-guest-layout>
