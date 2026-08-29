<?php

namespace Tests\Feature\Account;

use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AccountFeaturesTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeOrder(User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'currency' => 'TND',
            'subtotal' => 10000,
            'discount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 10000,
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ben Salah',
            'customer_email' => $user->email,
            'customer_phone' => '+216 98 000 000',
            'shipping_address' => '12 Rue de la Liberté',
            'shipping_city' => 'Tunis',
            'shipping_postal_code' => '1001',
            'shipping_country' => 'Tunisie',
            'public_token' => Str::random(64),
        ], $overrides));
    }

    public function test_dashboard_shows_stat_cards(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $delivered = $this->makeOrder($user, ['status' => 'delivered', 'total' => 5000]);
        $delivered->update(['completed_at' => now()]);
        $this->makeOrder($user, ['status' => 'pending', 'total' => 3000]);
        $this->makeOrder($user, ['status' => 'cancelled', 'total' => 9000]);

        $response = $this->actingAs($user)->get('/fr/account');

        $response->assertOk();
        $response->assertSee('Commandes', false);
        $response->assertSee('Total dépensé', false);
        // 8000 cents, excluding the cancelled order, formatted with 3 decimals.
        $response->assertSee('8 000,000', false);
    }

    public function test_orders_list_can_be_filtered_by_status(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $delivered = $this->makeOrder($user, ['status' => 'delivered']);
        $pending = $this->makeOrder($user, ['status' => 'pending']);

        $response = $this->actingAs($user)->get('/fr/account/orders?status=delivered');

        $response->assertOk();
        $response->assertSee($delivered->order_number, false);
        $response->assertDontSee($pending->order_number, false);
    }

    public function test_orders_list_can_be_searched_by_order_number(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $match = $this->makeOrder($user, ['order_number' => 'ORD-ABC-123456']);
        $other = $this->makeOrder($user);

        $response = $this->actingAs($user)->get('/fr/account/orders?search=ABC-123');

        $response->assertOk();
        $response->assertSee($match->order_number, false);
        $response->assertDontSee($other->order_number, false);
    }

    public function test_security_page_is_accessible_and_updates_password(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($user)->get('/fr/account/security')->assertOk();

        $this->actingAs($user)
            ->put('/fr/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_a_deactivated_customer_cannot_log_in(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);
        $user->update(['is_active' => false]);

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_profile_update_cannot_change_role_or_activation_status(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($user)->patch('/fr/account/profile', [
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Salah',
            'email' => $user->email,
            'role' => Role::Admin->value,
            'is_active' => false,
            'password' => 'should-not-be-considered',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->hasRole(Role::Admin->value));
        $this->assertTrue($user->is_active);
    }

    public function test_updating_an_address_does_not_change_existing_orders(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);
        $order = $this->makeOrder($user);

        $this->actingAs($user)->post('/fr/account/addresses', [
            'label' => 'Domicile',
            'first_name' => 'Karim',
            'last_name' => 'Trabelsi',
            'phone' => '+216 11 000 000',
            'address' => '8 Avenue Habib Bourguiba',
            'city' => 'Sfax',
            'postal_code' => '3000',
            'country' => 'Tunisie',
        ])->assertRedirect(route('account.addresses.index', ['locale' => 'fr']));

        $this->assertSame('Ahmed', $order->fresh()->customer_first_name);
        $this->assertSame('Ben Salah', $order->fresh()->customer_last_name);
    }

    public function test_stored_profile_data_is_escaped_against_xss(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($user)->patch('/fr/account/profile', [
            'first_name' => '<script>alert(1)</script>',
            'last_name' => 'Ben Salah',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $response = $this->actingAs($user)->get('/fr/account/profile');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_login_records_the_last_login_timestamp(): void
    {
        $user = $this->createUserWithRole(Role::Customer->value);

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('account.dashboard', ['locale' => 'fr']));

        $this->assertNotNull($user->refresh()->last_login_at);
    }
}
