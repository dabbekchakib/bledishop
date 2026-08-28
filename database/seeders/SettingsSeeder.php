<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingsSeeder extends Seeder
{
    /**
     * Insert default settings only when missing. Never overwrites custom values.
     */
    public function run(): void
    {
        foreach (config('settings.defaults', []) as $key => $meta) {
            $value = $meta['value'] ?? '';
            $type = $meta['type'] ?? SettingsService::inferType($value);

            Setting::query()->firstOrCreate([
                'key' => $key,
            ], [
                'value' => SettingsService::encodeValue($type, $value),
                'type' => $type,
                'group' => $meta['group'] ?? SettingsService::groupFor($key),
                'label' => $meta['label'] ?? Str::headline(Str::afterLast($key, '.')),
                'description' => $meta['description'] ?? null,
                'is_public' => $meta['is_public'] ?? SettingsService::isPublicByDefault($key),
            ]);
        }

        app(SettingsService::class)->clearAllCache();
    }
}
