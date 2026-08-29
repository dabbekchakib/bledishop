<x-account.layout active="profile" :title="__('account.profile_title')">

    <h1 class="text-2xl font-extrabold text-heading">{{ __('account.profile_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.profile_intro') }}</p>

    @if (session('status') === 'profile-updated')
        <p class="mt-4 rounded-xl border border-success/40 bg-surface px-4 py-3 text-sm text-success">{{ __('account.changes_saved') }}</p>
    @endif

    {{-- Profile information --}}
    <section class="mt-6 rounded-2xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold text-heading">{{ __('account.profile_info') }}</h2>
        <p class="mt-1 text-sm text-text-muted">{{ __('account.profile_info_hint') }}</p>

        <form method="post" action="{{ localized_route('account.profile.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('patch')

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-heading">{{ __('account.first_name') }}</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->name ? $user->firstName() : '') }}"
                           class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                           required autofocus autocomplete="given-name">
                    @error('first_name')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-heading">{{ __('account.last_name') }}</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->name ? $user->lastName() : '') }}"
                           class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                           required autocomplete="family-name">
                    @error('last_name')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-heading">{{ __('account.phone') }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                       autocomplete="tel">
                @error('phone')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-heading">{{ __('account.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary"
                       required autocomplete="username">
                @error('email')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2">
                        <p class="text-sm text-text-muted">
                            {{ __('account.email_unverified') }}
                            <button form="send-verification" type="button" class="text-primary hover:underline">{{ __('account.resend_verification') }}</button>
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-1 text-sm text-success">{{ __('account.verification_sent') }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-primary">{{ __('account.save') }}</button>
                @if (session('status') === 'profile-updated')
                    <span class="text-sm text-success">{{ __('account.saved') }}</span>
                @endif
            </div>
        </form>

        <form id="send-verification" method="post" action="{{ localized_route('verification.send') }}" class="hidden">
            @csrf
        </form>
    </section>

    {{-- Delete account --}}
    <section class="mt-6 rounded-2xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold text-heading text-danger">{{ __('account.delete_account') }}</h2>
        <p class="mt-1 text-sm text-text-muted">{{ __('account.delete_account_hint') }}</p>

        <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="btn-secondary mt-5">
            {{ __('account.delete_account') }}
        </button>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="{{ localized_route('account.profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-bold text-heading">{{ __('account.confirm_delete_title') }}</h2>
                <p class="mt-1 text-sm text-text-muted">{{ __('account.confirm_delete_hint') }}</p>

                <div class="mt-4">
                    <label for="password" class="sr-only">{{ __('account.password') }}</label>
                    <input id="password" name="password" type="password" placeholder="{{ __('account.password') }}"
                           class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                    @error('password', 'userDeletion')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">{{ __('account.cancel') }}</button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:opacity-90">{{ __('account.delete_account') }}</button>
                </div>
            </form>
        </x-modal>
    </section>

</x-account.layout>
