<?php

namespace App\Services;

use Illuminate\Support\Str;

class ThemeService
{
    private const PREFIX = 'theme.';

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Laravel validation rule enforcing a valid HEX (6 or 8 digits) color.
     * The pattern deliberately avoids "|" so the rule stays single-part when
     * Laravel splits string rules on pipes.
     */
    public static function hexRule(): string
    {
        return 'regex:/^#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?$/';
    }

    public static function validateColor(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1) {
            return strtolower($value);
        }

        return null;
    }

    /**
     * All theme colors keyed by their suffix (e.g. "primary_color" => "#2563EB").
     * Invalid stored values fall back to their default.
     */
    public function colors(bool $fresh = false): array
    {
        $actual = $this->settings->all($fresh);

        $colors = [];

        foreach ($this->themeDefaults() as $key => $meta) {
            if (($meta['type'] ?? null) !== 'color') {
                continue;
            }

            $suffix = Str::after($key, self::PREFIX);
            $default = (string) $meta['value'];

            $colors[$suffix] = static::validateColor((string) ($actual[$key] ?? $default)) ?? static::validateColor($default);
        }

        return $colors;
    }

    /**
     * Convert a HEX color to a space-separated RGB triplet usable with the
     * Tailwind "<alpha-value>" placeholder. Returns null when invalid.
     */
    public static function hexToRgbTriplet(string $value): ?string
    {
        $value = static::validateColor($value);

        if ($value === null) {
            return null;
        }

        if (strlen($value) === 9) {
            $value = substr($value, 0, 7);
        }

        $hex = substr($value, 1);

        return implode(' ', array_map('hexdec', str_split($hex, 2)));
    }

    /**
     * CSS custom properties map, e.g. ["--color-primary" => "37 99 235"].
     */
    public function cssVariables(bool $fresh = false): array
    {
        $variables = [];

        foreach ($this->colors($fresh) as $suffix => $hex) {
            $triplet = static::hexToRgbTriplet($hex);

            if ($triplet === null) {
                continue;
            }

            $variables[static::toCssVariable($suffix)] = $triplet;
        }

        return $variables;
    }

    /**
     * Compiled :root block that the frontend can echo inside a <style> tag.
     */
    public function css(bool $fresh = false): string
    {
        $lines = [];

        foreach ($this->cssVariables($fresh) as $variable => $triplet) {
            $lines[] = "    {$variable}: {$triplet};";
        }

        return ':root {'.PHP_EOL.implode(PHP_EOL, $lines).PHP_EOL.'}';
    }

    /**
     * Restore every theme setting to its default value.
     */
    public function reset(): void
    {
        foreach ($this->themeDefaults() as $key => $meta) {
            $this->settings->set($key, $meta['value']);
        }
    }

    public static function toCssVariable(string $suffix): string
    {
        $name = preg_replace('/_color$/', '', $suffix) ?? $suffix;

        return '--color-'.str_replace('_', '-', $name);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function themeDefaults(): array
    {
        $theme = [];

        foreach (config('settings.defaults', []) as $key => $meta) {
            if (str_starts_with($key, self::PREFIX)) {
                $theme[$key] = $meta;
            }
        }

        return $theme;
    }
}
