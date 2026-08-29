<?php

use App\Enums\Locale;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('auth', [
            Authenticate::class,
            EnsureUserIsActive::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            $locale = $request->segment(1);

            if (! in_array($locale, Locale::values(), true)) {
                $locale = setting('localization.default_locale', Locale::FR->value);
            }

            return localized_route('login', locale: $locale);
        });

        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
