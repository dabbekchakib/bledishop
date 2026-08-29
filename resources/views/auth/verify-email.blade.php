<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-heading">{{ __('auth.verify_email') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('auth.verification_hint') }}</p>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-4 rounded-xl border border-success/40 bg-background px-4 py-3 text-sm text-success">
            {{ __('auth.verification_sent') }}
        </p>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ localized_route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full justify-center">
                {{ __('account.resend_verification') }}
            </button>
        </form>

        <form method="POST" action="{{ localized_route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center text-sm text-text-muted hover:text-primary">
                {{ __('account.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
