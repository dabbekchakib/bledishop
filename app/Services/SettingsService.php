<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingsService
{
    public function all(bool $fresh = false): array
    {
        $values = [];

        foreach ($this->rows($fresh) as $key => $row) {
            $values[$key] = self::castValue($row['type'], $row['value']);
        }

        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public function has(string $key): bool
    {
        $values = $this->all();

        return array_key_exists($key, $values);
    }

    public function set(string $key, mixed $value, array $attributes = []): mixed
    {
        $existing = Setting::where('key', $key)->first();

        $type = $attributes['type'] ?? $existing?->type ?? self::inferType($value);

        $payload = [
            'value' => self::encodeValue($type, $value),
            'type' => $type,
            'group' => $attributes['group'] ?? $existing?->group ?? self::groupFor($key),
            'label' => $attributes['label'] ?? $existing?->label ?? Str::headline(Str::afterLast($key, '.')),
            'description' => $attributes['description'] ?? $existing?->description,
            'is_public' => $attributes['is_public'] ?? ($existing?->is_public ?? self::isPublicByDefault($key)),
        ];

        Setting::updateOrCreate(['key' => $key], $payload);

        $this->clearCache();

        return $value;
    }

    public function delete(string $key): void
    {
        Setting::where('key', $key)->delete();

        $this->clearCache();
    }

    public function publicSettings(bool $fresh = false): array
    {
        $public = [];

        foreach ($this->rows($fresh) as $key => $row) {
            if ($row['is_public']) {
                $public[$key] = self::castValue($row['type'], $row['value']);
            }
        }

        return $public;
    }

    public function isPublic(string $key): bool
    {
        $rows = $this->rows();

        return isset($rows[$key]) && (bool) $rows[$key]['is_public'];
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    public function clearAllCache(): void
    {
        $this->clearCache();
    }

    public function cacheKey(): string
    {
        return config('settings.cache_key', 'bledishop.settings.cache');
    }

    public static function castValue(string $type, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => (bool) $value,
            'array' => json_decode($value, true) ?? [],
            'json' => json_decode($value, true),
            default => (string) $value,
        };
    }

    public static function encodeValue(string $type, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return match ($type) {
            'integer' => (string) (int) $value,
            'decimal' => (string) (float) $value,
            'boolean' => $value ? '1' : '0',
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };
    }

    public static function inferType(mixed $value): string
    {
        if ($value === null) {
            return 'string';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value) || is_numeric($value)) {
            return 'decimal';
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    public static function groupFor(string $key): string
    {
        $prefix = Str::before($key, '.');

        return $prefix !== '' ? $prefix : 'general';
    }

    public static function isPublicByDefault(string $key): bool
    {
        $prefix = Str::before($key, '.');

        return in_array($prefix, ['site', 'shop', 'localization', 'seo', 'contact', 'social', 'theme'], true);
    }

    /**
     * Cached settings rows as plain arrays (no Eloquent models or
     * collections) so the serialized cache stays compatible with restricted
     * unserialization (serializable_classes = false in the database store).
     *
     * @return array<string, array{key: string, value: string, type: string, is_public: bool}>
     */
    private function rows(bool $fresh = false): array
    {
        $key = $this->cacheKey();

        $rows = $fresh ? null : Cache::get($key);

        if ($rows === null) {
            $rows = Setting::query()
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Setting $row): array => [
                    $row->key => [
                        'key' => $row->key,
                        'value' => (string) $row->value,
                        'type' => (string) $row->type,
                        'is_public' => (bool) $row->is_public,
                    ],
                ])
                ->all();

            Cache::put($key, $rows);
        }

        return $rows;
    }
}
