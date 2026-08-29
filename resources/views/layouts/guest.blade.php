<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ current_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ setting('site.name', config('app.name', 'Laravel')) }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Theme -->
        <x-theme-styles />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-text antialiased">
        <div class="flex min-h-screen flex-col items-center justify-start bg-background px-4 py-10 sm:py-16">
            <div class="flex w-full max-w-md items-center justify-between">
                <div>
                    <a href="{{ localized_route('home') }}" class="flex items-center gap-2" aria-label="{{ setting('site.name', config('app.name', 'Laravel')) }}">
                        @if (storefront_logo())
                            <img src="{{ storefront_logo() }}" alt="{{ setting('site.name', config('app.name', 'Laravel')) }}" class="h-10 w-auto max-w-[12rem] object-contain">
                        @else
                            <span class="text-xl font-extrabold tracking-tight text-heading">{{ setting('site.name', config('app.name', 'Laravel')) }}</span>
                        @endif
                    </a>
                </div>

                <x-language-switcher />
            </div>

            <div class="mt-6 w-full sm:max-w-md rounded-2xl border border-border bg-surface p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-8 max-w-md text-center text-xs text-text-muted">
                <a href="{{ localized_route('home') }}" class="hover:text-primary">{{ __('messages.nav_home') }}</a>
            </p>
        </div>
    </body>
</html>
