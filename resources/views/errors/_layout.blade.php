@php
    $siteName = config('app.name', 'BlediShop');
    $home = function_exists('localized_route') ? localized_route('home') : url('/');
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $status }} · {{ $siteName }}</title>
        <x-theme-styles />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-background px-4 font-sans antialiased">
        <div class="mx-auto max-w-md text-center">
            <p class="text-7xl font-extrabold tracking-tight text-primary">{{ $status }}</p>
            <h1 class="mt-4 text-2xl font-bold text-heading">{{ $title }}</h1>
            <p class="mt-3 text-text-muted">{{ $message }}</p>
            <a href="{{ $home }}" class="btn-primary mt-8 inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
                {{ $homeLabel }}
            </a>
        </div>
    </body>
</html>
