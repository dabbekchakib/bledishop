<?php

namespace App\Models;

use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * @return string|null The typed value for the given key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }

    public static function set(string $key, mixed $value, array $attributes = []): mixed
    {
        return app(SettingsService::class)->set($key, $value, $attributes);
    }

    public static function has(string $key): bool
    {
        return app(SettingsService::class)->has($key);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
