<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminLocalizationTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::SuperAdmin->value);
        $this->actingAs($user);
    }

    public function test_admin_sidebar_renders_french_by_default(): void
    {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('Produits')
            ->assertSee('Commandes')
            ->assertSee('Catalogue')
            ->assertSee('Clients')
            ->assertSee('Contenu')
            ->assertSee('Configuration');
    }

    public function test_admin_sidebar_renders_english_when_locale_is_english(): void
    {
        $this->withSession(['admin_locale' => 'en'])
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('Products')
            ->assertSee('Orders')
            ->assertSee('Catalogue')
            ->assertSee('Customers')
            ->assertSee('Content')
            ->assertSee('Configuration');
    }

    public function test_admin_sidebar_renders_arabic_when_locale_is_arabic(): void
    {
        $this->withSession(['admin_locale' => 'ar'])
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('منتجات')
            ->assertSee('طلبات')
            ->assertSee('الكتالوج')
            ->assertSee('العملاء')
            ->assertSee('المحتوى');
    }
}
