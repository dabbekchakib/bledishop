<?php

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
