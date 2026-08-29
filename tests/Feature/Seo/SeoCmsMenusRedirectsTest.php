<?php

namespace Tests\Feature\Seo;

use App\Enums\Role;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\UrlRedirect;
use App\Services\MenuService;
use App\Services\PageService;
use App\Services\SettingsService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class SeoCmsMenusRedirectsTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->seed(SettingsSeeder::class);
    }

    private function createPublishedPage(array $translations = []): Page
    {
        $default = $translations !== [] ? $translations : [
            'fr' => [
                'title' => 'À propos',
                'slug' => 'a-propos',
                'excerpt' => 'Notre histoire',
                'content' => '<h2>Bienvenue</h2><p>Voici notre page.</p>',
                'meta_title' => 'À propos | Boutique',
                'meta_description' => 'Découvrez notre histoire.',
            ],
        ];

        return app(PageService::class)->create(['template' => 'default', 'is_active' => true], $default);
    }

    // ------------------------------------------------------------------
    // CMS pages
    // ------------------------------------------------------------------

    public function test_a_published_page_renders_publicly(): void
    {
        $page = $this->createPublishedPage();

        $this->get('/fr/pages/a-propos')
            ->assertOk()
            ->assertSee('Bienvenue', false)
            ->assertSee('Notre histoire', false)
            ->assertSee('name="description"', false);
    }

    public function test_a_page_renders_in_english_and_arabic_when_translated(): void
    {
        $page = $this->createPublishedPage([
            'fr' => ['title' => 'À propos', 'slug' => 'a-propos', 'content' => 'Bienvenue'],
            'en' => ['title' => 'About', 'slug' => 'about', 'content' => 'Welcome'],
            'ar' => ['title' => 'من نحن', 'slug' => 'an-na', 'content' => 'مرحبا'],
        ]);

        $this->get('/en/pages/about')->assertOk()->assertSee('Welcome', false);
        $this->get('/ar/pages/an-na')->assertOk()->assertSee('مرحبا', false);
    }

    public function test_a_draft_page_is_not_publicly_visible(): void
    {
        app(PageService::class)->create(
            ['template' => 'default', 'is_active' => false],
            ['fr' => ['title' => 'Brouillon', 'slug' => 'brouillon', 'content' => 'Secret']],
        );

        $this->get('/fr/pages/brouillon')->assertNotFound();
    }

    public function test_an_unknown_page_slug_returns_404(): void
    {
        $this->get('/fr/pages/introuvable')->assertNotFound();
    }

    public function test_page_content_script_tags_are_sanitized(): void
    {
        app(PageService::class)->create(
            ['template' => 'default', 'is_active' => true],
            ['fr' => [
                'title' => 'Sécurité',
                'slug' => 'securite',
                'content' => '<p>Contenu</p><script>alert(1)</script><img src="x" onerror="alert(2)">',
            ]],
        );

        $this->get('/fr/pages/securite')
            ->assertOk()
            ->assertSee('Contenu', false)
            ->assertDontSee('alert(1)')
            ->assertDontSee('onerror');
    }

    public function test_arabic_page_renders_rtl_layout(): void
    {
        $this->createPublishedPage([
            'fr' => ['title' => 'À propos', 'slug' => 'a-propos', 'content' => 'Bienvenue'],
            'ar' => ['title' => 'من نحن', 'slug' => 'an-na', 'content' => 'مرحبا'],
        ]);

        $this->get('/ar/pages/an-na')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false);
    }

    // ------------------------------------------------------------------
    // Dynamic menus
    // ------------------------------------------------------------------

    public function test_the_main_menu_renders_in_the_storefront_header(): void
    {
        $menu = Menu::create(['name' => 'Principal', 'location' => 'main', 'is_active' => true]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'url',
            'target_url' => 'https://example.com/offers',
            'is_external' => true,
            'label' => ['fr' => 'Promotions', 'en' => 'Offers', 'ar' => 'عروض'],
        ]);

        app(MenuService::class)->clearCache();

        $this->get('/fr')->assertOk()->assertSee('Promotions', false);
        $this->get('/en')->assertOk()->assertSee('Offers', false);
        $this->get('/ar')->assertOk()->assertSee('عروض', false);
    }

    public function test_the_footer_menu_renders_its_items(): void
    {
        $menu = Menu::create(['name' => 'Pied', 'location' => 'footer', 'is_active' => true]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'url',
            'target_url' => 'https://example.com',
            'label' => ['fr' => 'Politique de confidentialité'],
        ]);

        app(MenuService::class)->clearCache();

        $this->get('/fr')->assertOk()->assertSee('Politique de confidentialité', false);
    }

    public function test_inactive_menu_is_not_rendered(): void
    {
        $menu = Menu::create(['name' => 'Masqué', 'location' => 'main', 'is_active' => false]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'url',
            'target_url' => 'https://example.com',
            'label' => ['fr' => 'Masqué'],
        ]);

        app(MenuService::class)->clearCache();

        $this->get('/fr')->assertOk()->assertDontSee('Masqué');
    }

    // ------------------------------------------------------------------
    // Sitemap & robots
    // ------------------------------------------------------------------

    public function test_the_sitemap_is_generated_with_localized_alternates(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->createPublishedPage();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/xml');
        $this->assertStringContainsString('<urlset', $response->getContent());
        $this->assertStringContainsString('hreflang', $response->getContent());
        $this->assertStringContainsString('/fr/', $response->getContent());
        $this->assertStringContainsString('/en/', $response->getContent());
        $this->assertStringContainsString('a-propos', $response->getContent());
    }

    public function test_the_sitemap_is_disabled_when_configured_off(): void
    {
        app(SettingsService::class)->set('seo.sitemap_enabled', false);

        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_robots_txt_contains_disallows_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString('User-agent: *', $response->getContent());
        $this->assertStringContainsString('Disallow:', $response->getContent());
        $this->assertStringContainsString('Sitemap:', $response->getContent());
    }

    public function test_robots_txt_omits_sitemap_when_disabled(): void
    {
        app(SettingsService::class)->set('seo.sitemap_enabled', false);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringNotContainsString('Sitemap:', $response->getContent());
    }

    // ------------------------------------------------------------------
    // URL redirects
    // ------------------------------------------------------------------

    public function test_a_301_redirect_is_applied(): void
    {
        $this->createPublishedPage();

        UrlRedirect::create([
            'source' => '/ancien-chemin',
            'destination' => '/fr/pages/a-propos',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/ancien-chemin')
            ->assertRedirect('/fr/pages/a-propos')
            ->assertStatus(301);
    }

    public function test_a_302_redirect_is_applied(): void
    {
        UrlRedirect::create([
            'source' => '/ancien-302',
            'destination' => '/fr/shop',
            'status_code' => 302,
            'is_active' => true,
        ]);

        $this->get('/ancien-302')
            ->assertRedirect('/fr/shop')
            ->assertStatus(302);
    }

    public function test_an_inactive_redirect_is_ignored(): void
    {
        UrlRedirect::create([
            'source' => '/ancien-inactif',
            'destination' => '/fr/shop',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $this->get('/ancien-inactif')->assertNotFound();
    }

    // ------------------------------------------------------------------
    // SEO output
    // ------------------------------------------------------------------

    public function test_the_homepage_emits_schema_org_json_ld_by_default(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/fr')
            ->assertOk()
            ->assertSee('application/ld+json', false);
    }

    public function test_schema_org_can_be_disabled(): void
    {
        $this->seed(CatalogSeeder::class);

        app(SettingsService::class)->set('seo.schema_org_enabled', false);

        $this->get('/fr')
            ->assertOk()
            ->assertDontSee('application/ld+json', false);
    }

    public function test_a_page_emits_canonical_and_open_graph_meta(): void
    {
        $this->createPublishedPage();

        $response = $this->get('/fr/pages/a-propos');

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('og:title', false);
        $response->assertSee('hreflang', false);
    }

    // ------------------------------------------------------------------
    // Permissions
    // ------------------------------------------------------------------

    public function test_seo_permissions_are_seeded_and_assigned(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        $manager = $this->createUserWithRole(Role::Manager->value);

        $this->assertTrue($staff->can('pages.view'));
        $this->assertTrue($staff->can('menus.view'));
        $this->assertTrue($staff->can('redirects.view'));
        $this->assertFalse($staff->can('pages.create'));
        $this->assertFalse($staff->can('menus.update'));
        $this->assertFalse($staff->can('redirects.delete'));

        $this->assertTrue($manager->can('pages.create'));
        $this->assertTrue($manager->can('menus.update'));
        $this->assertTrue($manager->can('seo.view'));
        $this->assertFalse($manager->can('seo.update'));
    }

    public function test_customer_cannot_access_cms_admin_resources(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $customer = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($customer)
            ->get('/admin/pages')
            ->assertForbidden();
    }

    public function test_staff_can_view_but_not_create_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/pages')->assertOk();
        $this->actingAs($staff)->get('/admin/pages/create')->assertForbidden();
    }

    public function test_manager_can_create_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = $this->createUserWithRole(Role::Manager->value);

        $this->actingAs($manager)->get('/admin/pages')->assertOk();
        $this->actingAs($manager)->get('/admin/pages/create')->assertOk();
    }
}
