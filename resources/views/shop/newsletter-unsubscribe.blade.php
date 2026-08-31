<x-shop-layout
    :title="__('shop.newsletter.unsubscribe_title')"
>

    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">

        <div class="rounded-2xl border border-border bg-surface p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full {{ $success ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    @if ($success)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c-1.73 0-2.813-1.874-1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    @endif
                </svg>
            </div>

            <h1 class="text-2xl font-extrabold tracking-tight text-heading">{{ __('shop.newsletter.unsubscribe_title') }}</h1>
            <p class="mt-3 text-text-muted">{{ $message }}</p>

            <a href="{{ localized_route('home') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-dark">
                {{ __('shop.newsletter.back_home') }}
            </a>
        </div>
    </div>

</x-shop-layout>
