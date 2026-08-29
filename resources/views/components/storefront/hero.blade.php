@props(['title' => '', 'subtitle' => ''])

<section class="relative overflow-hidden bg-surface">
    {{-- Decorative gradient --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -start-24 -top-24 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
        <div class="absolute -end-24 -bottom-24 h-72 w-72 rounded-full bg-secondary/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-3xl text-center">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-primary">{{ __('shop.hero_tagline') }}</p>
            <h1 class="text-4xl font-extrabold tracking-tight text-heading sm:text-5xl lg:text-6xl">
                {{ $title }}
            </h1>
            @if ($subtitle)
                <p class="mt-5 text-lg text-text-muted sm:text-xl">{{ $subtitle }}</p>
            @endif
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ localized_route('shop.index') }}" class="btn-primary">
                    {{ __('shop.hero_shop_now') }}
                </a>
                <a href="{{ localized_route('shop.index') }}" class="btn-secondary">
                    {{ __('shop.hero_browse') }}
                </a>
            </div>
        </div>
    </div>
</section>
