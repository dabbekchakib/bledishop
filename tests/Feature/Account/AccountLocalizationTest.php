<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_account_dashboard_renders_french_ltr(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/fr/account')
            ->assertOk()
            ->assertSee('<html lang="fr" dir="ltr">', false)
            ->assertSee('Tableau de bord', false);
    }

    public function test_account_dashboard_renders_english_ltr(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/en/account')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Dashboard', false);
    }

    public function test_account_dashboard_renders_arabic_rtl(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/ar/account')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('لوحة التحكم', false);
    }

    public function test_addresses_page_renders_in_the_active_locale(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create([
            'label' => 'Domicile',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Salah',
            'phone' => '+216 98 000 000',
            'address' => '12 Rue de la Liberté',
            'city' => 'Tunis',
            'country' => 'Tunisie',
        ]);

        $this->actingAs($user)
            ->get('/en/account/addresses')
            ->assertOk()
            ->assertSee('My addresses', false);
    }

    public function test_orders_page_renders_arabic_rtl(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/ar/account/orders')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('طلباتي', false);
    }

    public function test_security_page_renders_arabic_rtl(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/ar/account/security')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('الأمان', false);
    }

    public function test_security_page_renders_english_ltr(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/en/account/security')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Security', false);
    }
}
