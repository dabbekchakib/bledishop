@props(['categories' => []])

@php
    $mobileMenu = app(\App\Services\MenuService::class)->tree('mobile')->isNotEmpty()
        ? app(\App\Services\MenuService::class)->tree('mobile')
        : app(\App\Services\MenuService::class)->tree('main');
    $wishlistEnabled = (bool) setting('shop.wishlist_enabled', false);
@endphp

<div
    x-data
    x-show="$store.mobileMenu.open"
    x-cloak
    @keydown.escape.window="$store.mobileMenu.open = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('shop.nav_menu') }}"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40" x-on:click="$store.mobileMenu.open = false"></div>

    {{-- Drawer --}}
    <div
        class="absolute inset-y-0 start-0 flex w-80 max-w-[85%] flex-col bg-background shadow-xl"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full rtl:translate-x-full"
        x-transition:enter-end="translate-x-0 rtl:translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 rtl:translate-x-0"
        x-transition:leave-end="-translate-x-full rtl:translate-x-full"
    >
        {{-- Header --}}
        <div class="flex h-16 items-center justify-between border-b border-border px-4">
            <span class="text-lg font-bold tracking-tight text-heading">{{ setting('site.name', config('app.name', 'BlediShop')) }}</span>
            <button type="button" x-on:click="$store.mobileMenu.open = false" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-text-muted hover:bg-surface"
                aria-label="{{ __('shop.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="{{ __('messages.main_navigation') }}">
            <ul class="space-y-1">
                <li>
                    <a href="{{ localized_route('home') }}" class="block rounded-md px-3 py-2 text-base font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                        {{ __('messages.nav_home') }}
                    </a>
                </li>
                <li>
                    <a href="{{ localized_route('shop.index') }}" class="block rounded-md px-3 py-2 text-base font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                        {{ __('shop.nav_shop') }}
                    </a>
                </li>
            </ul>

            @if ($mobileMenu->isNotEmpty())
                <ul class="mt-1 space-y-1">
                    @foreach ($mobileMenu as $item)
                        <li x-data="{ expanded: false }">
                            <div class="flex items-center justify-between rounded-md ps-3">
                                <a href="{{ $item->urlFor() }}"
                                   @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                   class="flex-1 py-2 text-base font-medium text-text hover:text-primary"
                                   @if ($item->children->isEmpty()) x-on:click="$store.mobileMenu.open = false" @endif>
                                    {{ $item->labelFor() }}
                                </a>
                                @if ($item->children->isNotEmpty())
                                    <button type="button" x-on:click="expanded = !expanded"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-text-muted hover:bg-surface"
                                        :aria-expanded="expanded.toString()" aria-label="{{ $item->labelFor() }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            @if ($item->children->isNotEmpty())
                                <ul x-show="expanded" x-cloak class="ms-4 space-y-1 border-s border-border ps-3">
                                    @foreach ($item->children as $child)
                                        <li>
                                            <a href="{{ $child->urlFor() }}"
                                               @if ($child->target_blank) target="_blank" rel="noopener" @endif
                                               class="block py-1.5 text-sm text-text-muted hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                                                {{ $child->labelFor() }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($categories->isNotEmpty())
                <p class="mt-5 mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('shop.nav_categories') }}</p>
                <ul class="space-y-1">
                    @foreach ($categories as $category)
                        <li x-data="{ expanded: false }">
                            <div class="flex items-center justify-between rounded-md ps-3">
                                <a href="{{ localized_route('shop.category.show', ['slug' => $category->translatedSlug()]) }}"
                                   class="flex-1 py-2 text-sm font-medium text-text hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                                    {{ $category->translatedName() }}
                                </a>
                                @if ($category->children->isNotEmpty())
                                    <button type="button" x-on:click="expanded = !expanded"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-text-muted hover:bg-surface"
                                        :aria-expanded="expanded.toString()" aria-label="{{ $category->translatedName() }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            @if ($category->children->isNotEmpty())
                                <ul x-show="expanded" x-cloak class="ms-4 space-y-1 border-s border-border ps-3">
                                    @foreach ($category->children as $child)
                                        <li>
                                            <a href="{{ localized_route('shop.category.show', ['slug' => $child->translatedSlug()]) }}"
                                               class="block py-1.5 text-sm text-text-muted hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                                                {{ $child->translatedName() }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </nav>

        {{-- Account --}}
        <div class="border-t border-border px-4 py-4">
            @auth
                <p class="mb-2 px-1 text-sm font-semibold text-heading">{{ __('account.hello', ['name' => Auth::user()->name]) }}</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ localized_route('account.dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                            {{ __('account.nav_dashboard') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('account.orders.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                            {{ __('account.nav_orders') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('account.profile.edit') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                            {{ __('account.nav_profile') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ localized_route('account.addresses.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                            {{ __('account.nav_addresses') }}
                        </a>
                    </li>
                    @if ($wishlistEnabled)
                        <li>
                            <a href="{{ localized_route('shop.wishlist.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-text hover:bg-surface hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                                {{ __('shop.wishlist.title') }}
                            </a>
                        </li>
                    @endif
                    <li>
                        <form method="POST" action="{{ localized_route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-text hover:bg-surface hover:text-primary">
                                {{ __('account.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            @else
                <p class="mb-2 px-1 text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('account.title') }}</p>
                <div class="flex flex-col gap-2">
                    @if ($wishlistEnabled)
                        <a href="{{ localized_route('shop.wishlist.index') }}" class="inline-flex items-center justify-center rounded-md border border-border px-4 py-2 text-sm font-medium text-text hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                            {{ __('shop.wishlist.title') }}
                        </a>
                    @endif
                    <a href="{{ localized_route('login') }}" class="inline-flex items-center justify-center rounded-md border border-border px-4 py-2 text-sm font-medium text-text hover:text-primary" x-on:click="$store.mobileMenu.open = false">
                        {{ __('account.login') }}
                    </a>
                    <a href="{{ localized_route('register') }}" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-content hover:opacity-90" x-on:click="$store.mobileMenu.open = false">
                        {{ __('account.register') }}
                    </a>
                </div>
            @endauth
        </div>

        {{-- Footer --}}
        <div class="border-t border-border px-4 py-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('messages.language') }}</p>
            <x-language-switcher />
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('mobileMenu', { open: false });
    });
</script>
