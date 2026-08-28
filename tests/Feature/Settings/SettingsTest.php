<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingsService::class);

        $this->seed(SettingsSeeder::class);
    }

    public function test_settings_are_seeded_from_config_defaults(): void
    {
        $this->assertSame(
            count(config('settings.defaults')),
            Setting::query()->count(),
        );
    }

    public function test_seeding_again_does_not_duplicate_or_overwrite(): void
    {
        $this->service->set('site.name', 'Custom Name');

        $this->seed(SettingsSeeder::class);

        $this->assertSame(
            count(config('settings.defaults')),
            Setting::query()->count(),
        );

        $this->assertSame('Custom Name', $this->service->get('site.name'));
    }

    public function test_get_and_set_with_typed_values(): void
    {
        $this->service->set('shop.enabled', false);
        $this->service->set('shop.decimal_places', 2);
        $this->service->set('shipping.default_cost', 9.5);
        $this->service->set('localization.available_locales', ['fr', 'en']);

        $this->assertFalse($this->service->get('shop.enabled'));
        $this->assertSame(2, $this->service->get('shop.decimal_places'));
        $this->assertSame(9.5, $this->service->get('shipping.default_cost'));
        $this->assertSame(['fr', 'en'], $this->service->get('localization.available_locales'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', $this->service->get('missing.key', 'fallback'));
    }

    public function test_has_and_delete(): void
    {
        $this->assertTrue($this->service->has('site.name'));

        $this->service->delete('site.name');

        $this->assertFalse($this->service->has('site.name'));
        $this->assertDatabaseMissing('settings', ['key' => 'site.name']);
    }

    public function test_set_invalidates_the_settings_cache(): void
    {
        $this->assertSame('BlediShop', setting('site.name'));

        $this->service->set('site.name', 'Nouveau nom');

        $this->assertSame('Nouveau nom', setting('site.name'));
    }

    public function test_public_settings_exclude_private_groups(): void
    {
        $public = $this->service->publicSettings();

        $this->assertArrayHasKey('site.name', $public);
        $this->assertArrayHasKey('theme.primary_color', $public);

        $this->assertArrayNotHasKey('seller.name', $public);
        $this->assertArrayNotHasKey('tax.rate', $public);
    }

    public function test_helper_reads_public_and_private_settings(): void
    {
        $this->assertSame('BlediShop', setting('site.name'));
        $this->assertSame('Africa/Tunis', setting('localization.timezone'));
        $this->assertTrue(setting('shop.enabled'));
        $this->assertNull(setting('definitely.not.set'));
    }

    public function test_model_statics_delegate_to_service(): void
    {
        Setting::set('site.phone', '+216 00 000 000');

        $this->assertSame('+216 00 000 000', setting('site.phone'));
        $this->assertTrue(Setting::has('site.phone'));
    }
}
