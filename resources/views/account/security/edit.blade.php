<x-account.layout active="security" :title="__('account.security_title')">

    <h1 class="text-2xl font-extrabold text-heading">{{ __('account.security_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.security_intro') }}</p>

    @if (session('status') === 'password-updated')
        <p class="mt-4 rounded-xl border border-success/40 bg-surface px-4 py-3 text-sm text-success">{{ __('account.password_updated') }}</p>
    @endif

    <section class="mt-6 max-w-2xl rounded-2xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold text-heading">{{ __('account.security_info') }}</h2>
        <p class="mt-1 text-sm text-text-muted">{{ __('account.security_info_hint') }}</p>

        <form method="post" action="{{ localized_route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('put')

            <div>
                <label for="update_password_current_password" class="block text-sm font-medium text-heading">{{ __('account.current_password') }}</label>
                <input id="update_password_current_password" name="current_password" type="password"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                       autocomplete="current-password">
                @error('current_password', 'updatePassword')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password" class="block text-sm font-medium text-heading">{{ __('account.new_password') }}</label>
                <input id="update_password_password" name="password" type="password"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                       autocomplete="new-password">
                @error('password', 'updatePassword')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password_confirmation" class="block text-sm font-medium text-heading">{{ __('account.confirm_password') }}</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                       autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-primary">{{ __('account.password_save') }}</button>
                @if (session('status') === 'password-updated')
                    <span class="text-sm text-success">{{ __('account.saved') }}</span>
                @endif
            </div>
        </form>
    </section>

</x-account.layout>
