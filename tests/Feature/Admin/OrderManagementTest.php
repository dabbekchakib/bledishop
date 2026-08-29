<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Widgets\OrdersOverview;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\OrderStatusService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeOrder(?User $user = null): Order
    {
        return Order::create([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $user?->id,
            'status' => OrderStatus::Pending->value,
            'payment_status' => 'unpaid',
            'currency' => 'TND',
            'subtotal' => 249900,
            'discount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 249900,
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ben Salah',
            'customer_email' => $user?->email ?? 'guest@example.com',
            'customer_phone' => '+216 98 000 000',
            'shipping_address' => '12 Rue de la Liberté',
            'shipping_city' => 'Tunis',
            'shipping_postal_code' => '1001',
            'shipping_country' => 'Tunisie',
            'public_token' => Str::random(64),
        ]);
    }

    private function trackedProduct(int $stock = 10): Product
    {
        return Product::create([
            'type' => 'simple',
            'status' => 'active',
            'name' => 'Produit Test',
            'slug' => 'produit-test-'.Str::random(4),
            'sku' => 'TST-'.Str::upper(Str::random(5)),
            'price' => 100.00,
            'manage_stock' => true,
            'stock_quantity' => $stock,
            'low_stock_threshold' => 2,
        ]);
    }

    public function test_admin_can_list_and_view_orders(): void
    {
        $order = $this->makeOrder();
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $this->actingAs($admin)->get('/admin/orders')->assertOk();
        $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        $this->actingAs($admin)->get('/admin/orders/'.$order->id.'/edit')->assertOk();
    }

    public function test_staff_can_view_but_not_edit_orders(): void
    {
        $order = $this->makeOrder();
        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->assertTrue($staff->can('orders.view'));
        $this->assertFalse($staff->can('orders.update'));

        $this->actingAs($staff)->get('/admin/orders')->assertOk();
        $this->actingAs($staff)->get('/admin/orders/'.$order->id)->assertOk();
        $this->actingAs($staff)->get('/admin/orders/'.$order->id.'/edit')->assertForbidden();
    }

    public function test_manager_can_change_status_but_not_delete(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);

        $this->assertTrue($manager->can('orders.change_status'));
        $this->assertTrue($manager->can('orders.export'));
        $this->assertTrue($manager->can('orders.print'));
        $this->assertFalse($manager->can('orders.delete'));
    }

    public function test_a_customer_can_only_view_his_own_orders(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $own = $this->makeOrder($customer);
        $other = $this->makeOrder($this->createUserWithRole(Role::Customer->value));

        $this->assertTrue($customer->can('view', $own));
        $this->assertFalse($customer->can('view', $other));
    }

    public function test_print_route_requires_orders_print_permission(): void
    {
        $order = $this->makeOrder();

        $staff = $this->createUserWithRole(Role::Staff->value);
        $this->actingAs($staff)->get('/admin/orders/'.$order->id.'/print')->assertForbidden();

        $manager = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($manager)->get('/admin/orders/'.$order->id.'/print')->assertOk();

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);
        $this->actingAs($admin)->get('/admin/orders/'.$order->id.'/print')->assertOk();
    }

    public function test_print_view_renders_seller_and_customer_information(): void
    {
        $order = $this->makeOrder();
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $response = $this->actingAs($admin)->get('/admin/orders/'.$order->id.'/print');

        $response->assertOk();
        $response->assertSee($order->order_number, false);
        $response->assertSee('Ahmed Ben Salah', false);
        $response->assertSee('12 Rue de la Liberté', false);
    }

    public function test_csv_export_returns_a_download(): void
    {
        $order = $this->makeOrder();
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $response = $this->actingAs($admin)
            ->get('/admin/order-exports')
            ->assertOk()
            ->assertDownload();

        $content = $response->streamedContent();
        $this->assertStringContainsString($order->order_number, $content);
    }

    public function test_csv_export_respects_filters_and_permissions(): void
    {
        $pending = $this->makeOrder();
        $confirmed = $this->makeOrder();
        $confirmed->forceFill(['status' => OrderStatus::Confirmed->value])->save();

        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $content = $this->actingAs($admin)
            ->get('/admin/order-exports?status='.OrderStatus::Confirmed->value)
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($confirmed->order_number, $content);
        $this->assertStringNotContainsString($pending->order_number, $content);

        // Staff cannot export.
        $staff = $this->createUserWithRole(Role::Staff->value);
        $this->actingAs($staff)->get('/admin/order-exports')->assertForbidden();
    }

    public function test_a_valid_transition_is_recorded_in_history(): void
    {
        $order = $this->makeOrder();
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        app(OrderStatusService::class)->transition($order, OrderStatus::Confirmed, $admin, 'OK');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->confirmed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => OrderStatus::Pending->value,
            'new_status' => OrderStatus::Confirmed->value,
            'changed_by' => $admin->id,
            'note' => 'OK',
        ]);
    }

    public function test_an_invalid_transition_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->makeOrder();
        app(OrderStatusService::class)->transition($order, OrderStatus::Delivered);
    }

    public function test_delivered_pending_and_cancelled_processing_are_forbidden(): void
    {
        $service = app(OrderStatusService::class);

        $delivered = $this->makeOrder();
        $delivered->forceFill(['status' => OrderStatus::Delivered->value])->save();

        try {
            $service->transition($delivered, OrderStatus::Pending);
            $this->fail('delivered -> pending should be forbidden');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $cancelled = $this->makeOrder();
        $cancelled->forceFill(['status' => OrderStatus::Cancelled->value])->save();

        try {
            $service->transition($cancelled, OrderStatus::Processing);
            $this->fail('cancelled -> processing should be forbidden');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_transition_to_the_same_status_is_an_idempotent_noop(): void
    {
        $order = $this->makeOrder();
        $service = app(OrderStatusService::class);

        $service->transition($order, OrderStatus::Pending);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_delivery_sets_completed_at(): void
    {
        $order = $this->makeOrder();
        $order->forceFill(['status' => OrderStatus::Shipped->value])->save();

        app(OrderStatusService::class)->transition($order, OrderStatus::Delivered);

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
    }

    public function test_cancellation_restores_stock_exactly_once(): void
    {
        $product = $this->trackedProduct(10);
        $order = $this->makeOrder();
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => 'Produit Test',
            'sku' => $product->sku,
            'quantity' => 3,
            'unit_price' => 10000,
            'line_total' => 30000,
        ]);

        $service = app(OrderStatusService::class);

        $this->assertSame(10, $product->fresh()->stock_quantity);

        $service->transition($order, OrderStatus::Cancelled);

        $this->assertSame(13, $product->fresh()->stock_quantity);
        $this->assertTrue($order->fresh()->stockWasRestored());

        // Transitioning a cancelled order again is a no-op and restores nothing.
        $service->transition($order, OrderStatus::Cancelled);
        $this->assertSame(13, $product->fresh()->stock_quantity);
    }

    public function test_non_managed_items_do_not_restore_stock(): void
    {
        $product = $this->trackedProduct(10);
        $product->forceFill(['manage_stock' => false])->save();

        $order = $this->makeOrder();
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Produit Test',
            'quantity' => 2,
            'unit_price' => 10000,
            'line_total' => 20000,
        ]);

        app(OrderStatusService::class)->transition($order, OrderStatus::Cancelled);

        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_status_change_notifies_the_customer(): void
    {
        Notification::fake();

        $customer = $this->createUserWithRole(Role::Customer->value);
        $customer->update(['locale' => 'ar']);
        $order = $this->makeOrder($customer);

        app(OrderStatusService::class)->transition($order, OrderStatus::Confirmed);

        Notification::assertSentTo($customer, OrderStatusNotification::class);
    }

    public function test_status_change_does_not_notify_guest_orders(): void
    {
        Notification::fake();

        $order = $this->makeOrder();
        app(OrderStatusService::class)->transition($order, OrderStatus::Confirmed);

        Notification::assertNothingSent();
    }

    public function test_orders_overview_widget_reports_period_stats(): void
    {
        $this->makeOrder();

        Livewire::actingAs($this->createUserWithRole(Role::SuperAdmin->value))
            ->test(OrdersOverview::class)
            ->assertSet('period', 'month')
            ->assertSee('Aperçu des commandes');
    }

    public function test_orders_overview_can_switch_period(): void
    {
        Livewire::actingAs($this->createUserWithRole(Role::SuperAdmin->value))
            ->test(OrdersOverview::class)
            ->call('setPeriod', 'year')
            ->assertSet('period', 'year');
    }
}
