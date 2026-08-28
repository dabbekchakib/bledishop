<?php

namespace Tests\Feature\Settings;

use App\Services\SettingsService;
use App\Services\ThemeService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThemeService $theme;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = app(ThemeService::class);
        $this->settings = app(SettingsService::class);

        $this->seed(SettingsSeeder::class);
    }

    public function test_validate_color_accepts_6_and_8_digit_hex(): void
    {
        $this->assertSame('#2563eb', ThemeService::validateColor('#2563EB'));
        $this->assertSame('#2563ebff', ThemeService::validateColor('#2563EBFF'));
    }

    public function test_validate_color_rejects_invalid_values(): void
    {
        $this->assertNull(ThemeService::validateColor('2563EB'));
        $this->assertNull(ThemeService::validateColor('#GGGGGG'));
        $this->assertNull(ThemeService::validateColor('#FFF'));
        $this->assertNull(ThemeService::validateColor('red'));
    }

    public function test_hex_rule_validates_with_laravel_validator(): void
    {
        $rule = ThemeService::hexRule();

        $this->assertFalse(Validator::make(['c' => '#2563EB'], ['c' => $rule])->fails());
        $this->assertTrue(Validator::make(['c' => 'red'], ['c' => $rule])->fails());
    }

    public function test_hex_to_rgb_triplet(): void
    {
        $this->assertSame('37 99 235', ThemeService::hexToRgbTriplet('#2563EB'));
        $this->assertNull(ThemeService::hexToRgbTriplet('not-a-color'));
    }

    public function test_css_variables_are_rgb_triplets_for_tailwind(): void
    {
        $variables = $this->theme->cssVariables();

        $this->assertSame('37 99 235', $variables['--color-primary']);
        $this->assertArrayHasKey('--color-background', $variables);
        $this->assertArrayHasKey('--color-dark-background', $variables);
    }

    public function test_css_returns_root_block(): void
    {
        $css = $this->theme->css();

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--color-primary: 37 99 235;', $css);
    }

    public function test_custom_colors_are_reflected_in_css_variables(): void
    {
        $this->settings->set('theme.primary_color', '#FF0000');

        $this->assertSame('255 0 0', $this->theme->cssVariables()['--color-primary']);
    }

    public function test_invalid_stored_color_falls_back_to_default(): void
    {
        $this->settings->set('theme.primary_color', '#INVALID');

        $colors = $this->theme->colors();

        $this->assertSame('#2563eb', $colors['primary_color']);
    }

    public function test_reset_restores_default_theme_colors(): void
    {
        $this->settings->set('theme.primary_color', '#FF0000');
        $this->settings->set('theme.footer_color', '#111111');

        $this->theme->reset();

        $this->assertSame('#2563eb', strtolower((string) $this->settings->get('theme.primary_color')));
        $this->assertSame('#0f172a', strtolower((string) $this->settings->get('theme.footer_color')));
        $this->assertSame('37 99 235', $this->theme->cssVariables()['--color-primary']);
    }
}
