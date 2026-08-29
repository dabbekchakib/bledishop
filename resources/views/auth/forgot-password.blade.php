<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-heading">{{ __('auth.forgot_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('auth.forgot_password_hint') }}</p>

    @if (session('status'))
        <p class="mt-4 rounded-xl border border-success/40 bg-background px-4 py-3 text-sm text-success">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ localized_route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-heading">{{ __('auth.email') }}</label>
            <input id="email" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="email" name="email" :value="old('email')" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('auth.send_reset_link') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-text-muted">
        <a href="{{ localized_route('login') }}" class="font-medium text-primary hover:underline">{{ __('auth.login_link') }}</a>
    </p>
</x-guest-layout>
