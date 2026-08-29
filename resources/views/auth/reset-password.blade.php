<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-heading">{{ __('auth.reset_title') }}</h1>

    <form method="POST" action="{{ localized_route('password.store') }}" class="mt-6 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-heading">{{ __('auth.email') }}</label>
            <input id="email" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-heading">{{ __('auth.password') }}</label>
            <input id="password" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-heading">{{ __('auth.confirm_password') }}</label>
            <input id="password_confirmation" class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('auth.reset_password') }}
        </button>
    </form>
</x-guest-layout>
