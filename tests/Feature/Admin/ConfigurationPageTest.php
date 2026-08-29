<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Pages\Configuration;
use App\Models\User;
use App\Services\ThemeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ConfigurationPageTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/configuration')->assertRedirect();
    }

    public function test_staff_cannot_access_configuration(): void
    {
        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/configuration')->assertForbidden();
    }

    public function test_customer_cannot_access_configuration(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($customer)->get('/admin/configuration')->assertForbidden();
    }

    public function test_manager_with_settings_view_permission_can_access(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);

        $this->actingAs($manager)->get('/admin/configuration')->assertSuccessful();
    }

    public function test_super_admin_can_access(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $this->actingAs($admin)->get('/admin/configuration')->assertSuccessful();
    }

    public function test_super_admin_can_save_settings(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        Livewire::actingAs($admin)
            ->test(Configuration::class)
            ->set('data.site.name', 'BlediShop Tunisie')
            ->set('data.theme.primary_color', '#ff0000')
            ->set('data.shop.currency', 'EUR')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('BlediShop Tunisie', setting('site.name'));
        $this->assertSame('#ff0000', setting('theme.primary_color'));
        $this->assertSame('EUR', setting('shop.currency'));
    }

    public function test_super_admin_can_reset_theme_colors(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        Livewire::actingAs($admin)
            ->test(Configuration::class)
            ->set('data.theme.primary_color', '#ff0000')
            ->set('data.theme.footer_color', '#111111')
            ->call('save');

        $this->assertSame('#ff0000', setting('theme.primary_color'));

        Livewire::actingAs($admin)
            ->test(Configuration::class)
            ->call('resetTheme');

        $this->assertSame('#2563eb', strtolower((string) setting('theme.primary_color')));
        $this->assertSame('#0f172a', strtolower((string) setting('theme.footer_color')));
    }

    public function test_manager_cannot_save_without_settings_update_permission(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);

        Livewire::actingAs($manager)
            ->test(Configuration::class)
            ->call('save')
            ->assertStatus(403);
    }

    public function test_saved_values_are_used_by_the_theme(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        Livewire::actingAs($admin)
            ->test(Configuration::class)
            ->set('data.theme.primary_color', '#00ff00')
            ->call('save');

        $this->assertSame('0 255 0', app(ThemeService::class)->cssVariables()['--color-primary']);
    }

    public function test_save_header_action_is_wired_to_the_save_method(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $component = Livewire::actingAs($admin)->test(Configuration::class);

        $action = collect($component->instance()->getCachedHeaderActions())
            ->first(fn ($action): bool => $action->getName() === 'saveSettings');

        $this->assertNotNull($action);
        $this->assertFalse($action->canSubmitForm());
        $this->assertSame('save', $action->getLivewireClickHandler());
    }

    public function test_user_resource_creation_and_edit_pages_render(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $this->actingAs($admin)->get('/admin/users/create')->assertSuccessful();

        $target = User::factory()->create();

        $this->actingAs($admin)->get("/admin/users/{$target->id}/edit")->assertSuccessful();
    }

    public function test_settings_clear_cache_command_runs(): void
    {
        $this->assertSame(0, Artisan::call('settings:clear-cache'));
    }
}
