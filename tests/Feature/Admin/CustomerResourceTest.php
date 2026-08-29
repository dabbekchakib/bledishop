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

    public function test_customer_list_is_accessibly_only_with_customers_view_permission(): void
    {
        $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($this->createUserWithRole(Role::Staff->value))
            ->get('/admin/customers')
            ->assertOk();

        $this->actingAs($this->createUserWithRole(Role::Customer->value))
            ->get('/admin/customers')
            ->assertForbidden();
    }

    public function test_customer_detail_page_requires_customers_view(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($this->createUserWithRole(Role::Manager->value))
            ->get('/admin/customers/'.$customer->id)
            ->assertOk();

        $this->actingAs($this->createUserWithRole(Role::Customer->value))
            ->get('/admin/customers/'.$customer->id)
            ->assertForbidden();
    }

    public function test_export_requires_customers_export_permission(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $this->makeOrder($customer);

        $manager = $this->createUserWithRole(Role::Manager->value);

        $response = $this->actingAs($manager)->get('/admin/customer-exports');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $staff = $this->createUserWithRole(Role::Staff->value);
        $this->actingAs($staff)->get('/admin/customer-exports')->assertForbidden();
    }

    public function test_customer_export_respects_the_active_filter(): void
    {
        $active = $this->createUserWithRole(Role::Customer->value);
        $inactive = $this->createUserWithRole(Role::Customer->value);
        $inactive->update(['is_active' => false]);

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $response = $this->actingAs($admin)->get('/admin/customer-exports?active=false');

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringNotContainsString($active->email, $csv);
        $this->assertStringContainsString($inactive->email, $csv);
    }

    public function test_customer_export_respects_the_with_orders_filter(): void
    {
        $withOrders = $this->createUserWithRole(Role::Customer->value);
        $this->makeOrder($withOrders);
        $withoutOrders = $this->createUserWithRole(Role::Customer->value);

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $csv = $this->actingAs($admin)->get('/admin/customer-exports?with_orders=true')->streamedContent();

        $this->assertStringContainsString($withOrders->email, $csv);
        $this->assertStringNotContainsString($withoutOrders->email, $csv);
    }

    public function test_customer_policy_permissions_are_granular(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $manager = $this->createUserWithRole(Role::Manager->value);
        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->assertTrue($manager->can('customers.view'));
        $this->assertTrue($manager->can('customers.update'));
        $this->assertTrue($manager->can('customers.activate'));
        $this->assertTrue($manager->can('customers.export'));
        $this->assertFalse($manager->can('customers.delete'));

        $this->assertTrue($staff->can('customers.view'));
        $this->assertFalse($staff->can('customers.update'));
        $this->assertFalse($staff->can('customers.activate'));
        $this->assertFalse($staff->can('customers.export'));
        $this->assertFalse($staff->can('customers.delete'));

        $this->assertFalse($customer->can('customers.view'));
    }
}
