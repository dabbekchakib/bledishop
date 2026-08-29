@props(['categories' => [], 'brands' => []])

@php
    $siteName = setting('site.name', config('app.name', 'BlediShop'));
    $logo = storefront_logo();
    $current = request()->routeIs('home') ? 'home'
        : (request()->routeIs('shop.index') ? 'shop'
        : (request()->routeIs('shop.category.show') ? 'categories' : ''));
@endphp

<header
    x-data="{
        scrolled: false,
        menuOpen: false,
        searchOpen: false,
        init() {
            const onScroll = () => { this.scrolled = window.scrollY > 8; };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        },
    }"
    class="sticky top-0 z-40 border-b border-border bg-header text-header-text transition-shadow duration-300"
    :class="scrolled ? 'shadow-sm' : 'shadow-none'"
>
    <div class="mx-auto flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        {{-- Mobile menu toggle --}}
        <button
            type="button"
            class="-ml-1 inline-flex h-10 w-10 items-center justify-center rounded-md text-header-text hover:bg-surface lg:hidden"
            aria-label="{{ __('shop.nav_menu') }}"
            x-on:click="Alpine.store('mobileMenu').open = true"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
            </svg>
        </button>

        {{-- Logo --}}
        <a href="{{ localized_route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ $siteName }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-9 w-auto max-w-[10rem] object-contain">
            @else
                <span class="text-xl font-extrabold tracking-tight text-header-text">{{ $siteName }}</span>
            @endif
        </a>

        {{-- Desktop navigation + mega menu --}}
        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('messages.main_navigation') }}">
            <a href="{{ localized_route('home') }}"
               class="rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $current === 'home' ? 'text-primary' : 'text-header-text hover:text-primary' }}">
                {{ __('messages.nav_home') }}
            </a>
            <a href="{{ localized_route('shop.index') }}"
               class="rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $current === 'shop' ? 'text-primary' : 'text-header-text hover:text-primary' }}">
                {{ __('shop.nav_shop') }}
            </a>

            {{-- Mega menu --}}
            @if ($categories->isNotEmpty())
                <div
                    x-data="{ open: false }"
                    @mouseenter="open = true"
                    @mouseleave="open = false"
                    class="relative"
                >
                    <button
                        type="button"
                        x-on:click="open = !open"
                        x-on:keydown.escape="open = false"
                        x-on:keydown.down.prevent="open = true"
                        class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $current === 'categories' ? 'text-primary' : 'text-header-text hover:text-primary' }}"
                        aria-haspopup="true"
                        :aria-expanded="open.toString()"
                    >
                        {{ __('shop.nav_categories') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @keydown.escape.window="open = false"
                        class="absolute end-0 top-full w-[36rem] origin-top rounded-2xl border border-border bg-surface shadow-lg"
                    >
                        <div class="grid grid-cols-3 gap-6 p-6">
                            @foreach ($categories as $category)
                                <div>
                                    <a href="{{ localized_route('shop.category.show', ['slug' => $category->translatedSlug()]) }}"
                                       class="text-sm font-semibold text-heading hover:text-primary">
                                        {{ $category->translatedName() }}
                                    </a>
                                    @if ($category->children->isNotEmpty())
                                        <ul class="mt-2 space-y-1.5">
                                            @foreach ($category->children as $child)
                                                <li>
                                                    <a href="{{ localized_route('shop.category.show', ['slug' => $child->translatedSlug()]) }}"
                                                       class="text-sm text-text-muted transition-colors hover:text-primary">
                                                        {{ $child->translatedName() }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($brands->isNotEmpty())
                <a href="{{ localized_route('shop.index') }}"
                   class="rounded-md px-3 py-2 text-sm font-medium text-header-text transition-colors hover:text-primary">
                    {{ __('shop.nav_brands') }}
                </a>
            @endif
        </nav>

        {{-- Actions: search, language, account, cart --}}
        <div class="flex items-center gap-1.5 sm:gap-2">

            {{-- Search (desktop) --}}
            <form action="{{ localized_route('shop.search') }}" method="GET" role="search" class="relative hidden md:block">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ request()->query('q') }}"
                    placeholder="{{ __('shop.search_placeholder') }}"
                    class="w-44 rounded-full border-border bg-surface py-2 pe-4 ps-9 text-sm text-text placeholder:text-text-muted focus:border-primary focus:ring-primary xl:w-56"
                >
            </form>

            {{-- Search (mobile) --}}
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md text-header-text hover:bg-surface md:hidden"
                aria-label="{{ __('shop.search_label') }}"
                x-on:click="searchOpen = true"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                </svg>
            </button>

            <x-language-switcher />

            {{-- Account --}}
            <div class="hidden sm:block">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-md px-2 text-sm font-medium text-header-text hover:text-primary"
                       aria-label="{{ __('shop.account') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0v.4h-15v-.4z"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-md px-2 text-sm font-medium text-header-text hover:text-primary"
                       aria-label="{{ __('shop.account') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0v.4h-15v-.4z"/>
                        </svg>
                    </a>
                @endauth
            </div>

            {{-- Cart (prepared for Prompt 7) --}}
            <a href="#" aria-disabled="true" class="relative inline-flex h-10 items-center justify-center rounded-md px-2 text-header-text"
               aria-label="{{ __('shop.cart') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Mobile search drawer --}}
    <div x-show="searchOpen" x-cloak class="border-t border-border bg-header px-4 py-3 md:hidden">
        <form action="{{ localized_route('shop.search') }}" method="GET" role="search">
            <label for="mobile-search" class="sr-only">{{ __('shop.search_label') }}</label>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                </svg>
                <input
                    id="mobile-search"
                    type="search"
                    name="q"
                    value="{{ request()->query('q') }}"
                    placeholder="{{ __('shop.search_placeholder') }}"
                    class="w-full rounded-full border-border bg-surface py-2 pe-4 ps-9 text-sm text-text placeholder:text-text-muted focus:border-primary focus:ring-primary"
                >
            </div>
        </form>
    </div>
</header>
