<?php

use App\Services\LocalizationService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Retrieve a setting value, or all settings when no key is provided.
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingsService::class);

        return $key === null ? $service->all() : $service->get($key, $default);
    }
}

if (! function_exists('current_locale')) {
    /**
     * The active locale for the current request.
     */
    function current_locale(): string
    {
        return app(LocalizationService::class)->currentLocale();
    }
}

if (! function_exists('current_direction')) {
    /**
     * HTML text direction for the active locale (ltr|rtl).
     */
    function current_direction(): string
    {
        return app(LocalizationService::class)->direction();
    }
}

if (! function_exists('is_rtl')) {
    /**
     * Whether the active locale should be rendered right-to-left.
     */
    function is_rtl(): bool
    {
        return app(LocalizationService::class)->isRtl();
    }
}

if (! function_exists('localized_route')) {
    /**
     * Generate a URL for a localized route using the given (or current) locale.
     *
     * @param  array<string, mixed>  $parameters
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $service = app(LocalizationService::class);

        $locale ??= $service->currentLocale();

        return route($name, array_merge(['locale' => $locale], $parameters));
    }
}

if (! function_exists('format_price')) {
    /**
     * Format a monetary value using the configured currency, symbol position
     * and decimal places. Purely presentational — the server stays the source
     * of truth for all financial calculations.
     */
    function format_price(float|int|string|null $price, ?int $decimals = null): string
    {
        $value = (float) ($price ?? 0);

        $symbol = (string) setting('shop.currency_symbol', 'DT');
        $position = (string) setting('shop.currency_position', 'after');
        $decimalPlaces = $decimals ?? (int) setting('shop.decimal_places', 3);

        if ($decimalPlaces < 0) {
            $decimalPlaces = 0;
        }

        $formatted = number_format($value, $decimalPlaces, ',', ' ');

        return $position === 'before'
            ? $symbol.' '.$formatted
            : $formatted.' '.$symbol;
    }
}

if (! function_exists('storefront_image')) {
    /**
     * Absolute URL for a stored catalog image (public disk). Falls back to a
     * bundled placeholder when no path is available.
     */
    function storefront_image(?string $path, ?string $fallback = null): string
    {
        if (filled($path)) {
            return Storage::url($path);
        }

        return $fallback ?? asset('images/placeholder.svg');
    }
}

if (! function_exists('storefront_logo')) {
    /**
     * URL of the configured site logo, or null when absent.
     */
    function storefront_logo(): ?string
    {
        $path = (string) setting('site.logo', '');

        return filled($path) ? storefront_image($path) : null;
    }
}
