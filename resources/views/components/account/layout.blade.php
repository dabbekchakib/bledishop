@props([
    'title' => '',
    'metaDescription' => '',
    'active' => 'dashboard',
    'robots' => 'noindex, nofollow',
])

@php
    $links = [
        ['key' => 'dashboard', 'label' => __('account.nav_dashboard'), 'route' => 'account.dashboard'],
        ['key' => 'orders', 'label' => __('account.nav_orders'), 'route' => 'account.orders.index'],
        ['key' => 'profile', 'label' => __('account.nav_profile'), 'route' => 'account.profile.edit'],
        ['key' => 'addresses', 'label' => __('account.nav_addresses'), 'route' => 'account.addresses.index'],
    ];
@endphp

<x-shop-layout :title="$title ?: __('account.title')" :meta-description="$metaDescription" :robots="$robots">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('account.title'), 'url' => localized_route('account.dashboard')],
            ['label' => $title ?: __('account.title')],
        ]" />

        <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
            {{-- Sidebar --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <nav class="rounded-2xl border border-border bg-surface p-2" aria-label="{{ __('account.title') }}">
                    <a href="{{ localized_route('home') }}" class="flex items-center gap-3 px-3 py-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">
                            {{ strtoupper(substr(trim((string) auth()->user()?->name), 0, 1) ?: '?') }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-heading">{{ auth()->user()?->name }}</span>
                            <span class="block truncate text-xs text-text-muted">{{ auth()->user()?->email }}</span>
                        </span>
                    </a>
                    <div class="my-2 border-t border-border"></div>
                    <ul class="space-y-0.5">
                        @foreach ($links as $link)
                            <li>
                                <a href="{{ localized_route($link['route']) }}"
                                   class="block rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $active === $link['key'] ? 'bg-primary/10 text-primary' : 'text-text hover:bg-surface hover:text-primary' }}">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="my-2 border-t border-border"></div>
                    <form method="POST" action="{{ localized_route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-text transition-colors hover:bg-surface hover:text-primary">
                            {{ __('account.logout') }}
                        </button>
                    </form>
                </nav>
            </aside>

            {{-- Content --}}
            <section class="min-w-0">
                {{ $slot }}
            </section>
        </div>
    </div>
</x-shop-layout>
