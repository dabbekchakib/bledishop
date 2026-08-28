<?php

namespace Tests\Feature;

use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The root URL redirects to the active locale (default: fr) unless the
     * visitor already has a preference.
     */
    public function test_the_application_root_redirects_to_the_default_locale(): void
    {
        $this->seed(SettingsSeeder::class);

        app(SettingsService::class)->set('localization.browser_detection_enabled', false);

        $this->get('/')->assertRedirect('/fr');
    }
}
