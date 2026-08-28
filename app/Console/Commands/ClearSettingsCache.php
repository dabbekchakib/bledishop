<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;

class ClearSettingsCache extends Command
{
    protected $signature = 'settings:clear-cache';

    protected $description = 'Clear the cached settings and theme values';

    public function handle(SettingsService $settings): int
    {
        $settings->clearAllCache();

        $this->info('Settings cache cleared.');

        return self::SUCCESS;
    }
}
