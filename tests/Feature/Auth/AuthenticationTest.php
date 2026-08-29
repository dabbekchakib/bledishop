<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/fr/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.dashboard', ['locale' => 'fr']));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_cannot_authenticate_when_disabled(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/fr/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_users_are_redirected_to_their_intended_route_after_login(): void
    {
        $user = User::factory()->create();

        $this->session(['url.intended' => '/'.current_locale().'/account/addresses']);

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/fr/account/addresses');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/fr/logout');

        $this->assertGuest();
        $response->assertRedirect('/fr');
    }

    public function test_users_can_register(): void
    {
        $response = $this->post('/fr/register', [
            'name' => 'New Client',
            'email' => 'client@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.dashboard', ['locale' => 'fr']));

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'is_active' => true,
        ]);
    }
}
