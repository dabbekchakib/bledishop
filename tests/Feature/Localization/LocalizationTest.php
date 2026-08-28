<?php

namespace Tests\Feature\Localization;

use App\Models\User;
use App\Services\LocalizationService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private LocalizationService $localization;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale(config('app.locale', 'fr'));

        $this->seed(SettingsSeeder::class);

        $this->localization = app(LocalizationService::class);
    }

    public function test_enabled_locales_serve_the_home_page_with_language_and_direction(): void
    {
        $this->get('/fr')
            ->assertOk()
            ->assertSee('<html lang="fr" dir="ltr">', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);

        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false);
    }

    public function test_root_redirects_to_the_active_locale(): void
    {
        app(SettingsService::class)->set('localization.browser_detection_enabled', false);

        $this->get('/')->assertRedirect('/fr');
    }

    public function test_root_redirects_to_browser_locale_when_no_preference_exists(): void
    {
        $this->get('/', ['Accept-Language' => 'ar,fr;q=0.9'])->assertRedirect('/ar');
    }

    public function test_unknown_locale_returns_not_found(): void
    {
        $this->get('/xx')->assertNotFound();
    }

    public function test_disabled_locale_returns_not_found(): void
    {
        app(SettingsService::class)->set('localization.available_locales', ['fr', 'en']);

        $this->get('/ar')->assertNotFound();
    }

    public function test_content_is_translated_per_locale(): void
    {
        $this->get('/fr')->assertSee('Bienvenue dans votre boutique');
        $this->get('/en')->assertSee('Welcome to your store');
        $this->get('/ar')->assertSee('مرحباً بكم في متجركم');
    }

    public function test_hreflang_alternates_are_rendered_for_localized_pages(): void
    {
        $this->get('/fr')
            ->assertSee('rel="alternate" hreflang="fr"', false)
            ->assertSee('rel="alternate" hreflang="ar"', false)
            ->assertSee('rel="alternate" hreflang="en"', false);
    }

    public function test_localized_home_link_is_generated_for_the_active_locale(): void
    {
        $this->get('/ar')->assertSee('href="'.url('/ar').'"', false);
    }

    public function test_browser_language_is_detected_without_explicit_preference(): void
    {
        $this->get('/', ['Accept-Language' => 'ar,fr;q=0.9'])->assertRedirect('/ar');
    }

    public function test_url_locale_overrides_session_preference(): void
    {
        $this->withSession(['locale' => 'en'])->get('/fr')->assertSee('Bienvenue dans votre boutique');
    }

    public function test_authenticated_user_locale_takes_priority_over_browser_detection(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);

        $this->actingAs($user)
            ->get('/', ['Accept-Language' => 'fr,fr-FR;q=0.9'])
            ->assertRedirect('/ar');
    }

    public function test_switching_language_preserves_the_current_page(): void
    {
        $this->from('/fr')
            ->get('/language/ar?redirect=/fr')
            ->assertRedirect('/ar');
    }

    public function test_switching_language_sets_session_and_cookie(): void
    {
        $this->get('/language/ar?redirect=/fr')
            ->assertRedirect('/ar')
            ->assertSessionHas('locale', 'ar')
            ->assertCookie('locale', 'ar');
    }

    public function test_switching_language_without_redirect_falls_back_to_locale_root(): void
    {
        $this->get('/language/ar')->assertRedirect('/ar');
    }

    public function test_switching_language_rejects_external_redirect_targets(): void
    {
        $this->get('/language/ar?redirect=https://evil.example')->assertRedirect('/ar');
        $this->get('/language/ar?redirect=//evil.example')->assertRedirect('/ar');
    }

    public function test_switching_to_a_disabled_locale_returns_not_found(): void
    {
        app(SettingsService::class)->set('localization.available_locales', ['fr', 'en']);

        $this->get('/language/ar')->assertNotFound();
    }

    public function test_switching_language_updates_authenticated_user_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/language/ar?redirect=/fr');

        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_switching_language_does_not_persist_for_guests(): void
    {
        $this->get('/language/ar?redirect=/fr')->assertRedirect('/ar');
    }

    public function test_service_filters_unknown_locales_from_available_list(): void
    {
        app(SettingsService::class)->set('localization.available_locales', ['fr', 'xx', 'en', 'ar']);

        $this->assertSame(['fr', 'en', 'ar'], $this->localization->availableLocales());
    }

    public function test_direction_helpers_follow_the_active_locale(): void
    {
        app()->setLocale('ar');
        $this->assertSame('rtl', current_direction());
        $this->assertTrue(is_rtl());

        app()->setLocale('fr');
        $this->assertSame('ltr', current_direction());
        $this->assertFalse(is_rtl());
    }
}
