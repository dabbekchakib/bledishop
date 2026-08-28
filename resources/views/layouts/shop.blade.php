<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ setting('seo.title', setting('site.name', config('app.name', 'Laravel'))) }}</title>
        <meta name="description" content="{{ setting('seo.description') }}">

        @php($localizedUrls = app(\App\Services\LocalizationService::class)->localizedUrlsForCurrentRequest())
        @foreach ($localizedUrls as $code => $path)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ url($path) }}" />
        @endforeach

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Theme -->
        <x-theme-styles />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col font-sans antialiased">
        <header class="border-b border-border bg-header text-header-text">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <a href="{{ localized_route('home') }}" class="text-lg font-bold tracking-tight">
                        {{ setting('site.name', config('app.name', 'Laravel')) }}
                    </a>

                    <nav class="hidden items-center gap-6 sm:flex" aria-label="{{ __('messages.main_navigation') }}">
                        <a href="{{ localized_route('home') }}" class="text-sm font-medium text-text-muted transition-colors hover:text-text">
                            {{ __('messages.nav_home') }}
                        </a>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <x-language-switcher />
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-footer text-footer-text">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-3">
                    <div>
                        <p class="text-base font-semibold">{{ setting('site.name', config('app.name', 'Laravel')) }}</p>
                        <p class="mt-2 text-sm text-footer-text opacity-80">
                            {{ setting('site.description', __('messages.footer_tagline')) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide">{{ __('messages.language') }}</p>
                        <div class="mt-3">
                            <x-language-switcher />
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide">{{ __('messages.contact') }}</p>
                        <ul class="mt-3 space-y-1 text-sm text-footer-text opacity-80" dir="ltr">
                            @if (filled(setting('contact.phone')))
                                <li>{{ setting('contact.phone') }}</li>
                            @endif
                            @if (filled(setting('contact.email')))
                                <li>{{ setting('contact.email') }}</li>
                            @endif
                            @if (filled(setting('contact.address')))
                                <li>{{ setting('contact.address') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="mt-8 border-t border-white/10 pt-6 text-center text-xs opacity-70">
                    &copy; {{ now()->format('Y') }} {{ setting('site.name', config('app.name', 'Laravel')) }}
                </div>
            </div>
        </footer>
    </body>
</html>