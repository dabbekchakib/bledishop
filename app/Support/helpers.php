<?php

use App\Services\LocalizationService;
use App\Services\SettingsService;

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
