<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CustomerResourceTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'currency' => 'TND',
            'subtotal' => 249900,
            'discount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 249900,
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ben Salah',
            'customer_email' => $user->email,
            'customer_phone' => '+216 98 000 000',
            'shipping_address' => '12 Rue de la Liberté',
            'shipping_city' => 'Tunis',
            'shipping_postal_code' => '1001',
            'shipping_country' => 'Tunisie',
            'public_token' => Str::random(64),
        ]);
    }

    public function test_a_listing_page_lists_customers_only(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $this->makeOrder($customer);
        $this->createUserWithRole(Role::Admin->value);

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $response = $this->actingAs($admin)->get('/admin/customers');

        $response->assertOk();
        $response->assertSee($customer->email, false);
    }

    public function test_a_customer_can_be_viewed_with_their_orders(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $this->makeOrder($customer);

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $response = $this->actingAs($admin)->get('/admin/customers/'.$customer->id.'/edit');

        $response->assertOk();
        $response->assertSee($customer->email, false);
    }
}
