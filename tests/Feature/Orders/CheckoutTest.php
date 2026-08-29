<?php

namespace Tests\Feature\Orders;

use App\Enums\Role;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);
    }

    private function product(string $frSlug): Product
    {
        return Product::query()
            ->with(['variants'])
            ->whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', $frSlug))
            ->firstOrFail();
    }

    private function customer(): array
    {
        return [
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Salah',
            'phone' => '+216 98 000 000',
            'email' => 'ahmed@example.com',
            'address' => '12 Rue de la Liberté',
            'city' => 'Tunis',
            'postal_code' => '1001',
            'country' => 'Tunisie',
            'notes' => 'Merci',
        ];
    }

    private function addCart(int $productId, ?int $variantId, int $quantity = 1): void
    {
        $this->postJson('/fr/cart/add', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function test_a_guest_can_checkout_and_creates_an_order(): void
    {
        Mail::fake();
        Notification::fake();

        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 2);

        $response = $this->post('/fr/checkout', $this->customer());

        $response->assertRedirect();
        $order = Order::latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status->value);
        $this->assertSame('unpaid', $order->payment_status->value);
        $this->assertSame(2, $order->items()->first()->quantity);
        $this->assertSame('CMD-', substr($order->order_number, 0, 4));

        // 2499.00 (= 249900 cents) x 2 = 499800 cents
        $this->assertSame(499800, $order->subtotal);
        $this->assertSame(499800, $order->total);
        $this->assertSame('Ahmed', $order->customer_first_name);
        $this->assertSame(40, $product->fresh()->stock_quantity);

        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_order_snapshot_keeps_product_name_and_price(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $this->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame('Smartphone X Pro', $item->product_name);
        $this->assertSame(249900, $item->unit_price);
        $this->assertSame(249900, $item->line_total);
        $this->assertSame('PRD-SMARTPHONE-X-PRO', $item->sku);
        $this->assertIsArray($item->product_snapshot);
        $this->assertSame('Smartphone X Pro', $item->product_snapshot['name']);
        $this->assertSame('Ahmed', $order->customer_first_name);
    }

    public function test_a_checked_out_product_has_its_stock_decremented(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 3);

        $this->post('/fr/checkout', $this->customer());

        $this->assertSame(39, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'decrease',
            'quantity' => -3,
        ]);
    }

    public function test_checkout_clears_the_cart(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $this->post('/fr/checkout', $this->customer());

        $cartResponse = $this->get('/fr/cart');

        $cartResponse->assertSee('Votre panier est vide', false);
    }

    public function test_checkout_with_variant_creates_a_variant_line(): void
    {
        $product = $this->product('pull-premium');
        $variant = $product->variants()->firstOrFail();
        $this->addCart($product->id, $variant->id, 1);

        $this->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame($product->id, (int) $item->product_id);
        $this->assertSame($variant->id, (int) $item->product_variant_id);
        $this->assertSame(12900, $item->unit_price);
        $this->assertNotNull($item->variant_name);

        // variant stock was decremented
        $this->assertSame(9, $variant->fresh()->stock_quantity);
    }

    public function test_checkout_recomputes_price_server_side_after_a_price_change(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $product->forceFill(['price' => 2100.00])->save();

        $this->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame(210000, $order->subtotal);
        $this->assertSame(210000, $order->items()->first()->unit_price);
    }

    public function test_an_authenticated_user_checkout_links_the_order_to_his_account(): void
    {
        $user = User::factory()->create();
        $product = $this->product('smartphone-x-pro');

        $this->actingAs($user);
        $this->addCart($product->id, null, 1);

        $this->actingAs($user)->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame($user->id, (int) $order->user_id);
    }

    public function test_account_creation_during_checkout_creates_a_customer_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $payload = $this->customer();
        $payload['create_account'] = 1;
        $payload['password'] = 'password123';
        $payload['password_confirmation'] = 'password123';

        $response = $this->post('/fr/checkout', $payload);

        $response->assertRedirect();

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();

        // An account is now linked to the order and the customer is logged in.
        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($user->id, (int) $order->user_id);
        $this->assertTrue($user->hasRole(Role::Customer->value));
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_creation_with_an_existing_email_does_not_duplicate(): void
    {
        User::factory()->create(['email' => 'ahmed@example.com']);
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $payload = $this->customer();
        $payload['create_account'] = 1;
        $payload['password'] = 'password123';
        $payload['password_confirmation'] = 'password123';

        $this->post('/fr/checkout', $payload);

        $this->assertSame(1, User::where('email', 'ahmed@example.com')->count());

        // The guest order is still placed.
        $order = Order::latest('id')->firstOrFail();
        $this->assertNull($order->user_id);
    }

    public function test_password_is_required_when_creating_an_account(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $payload = $this->customer();
        $payload['create_account'] = 1;

        $response = $this->post('/fr/checkout', $payload);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_with_empty_password_fields_is_not_rejected(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        // A real browser submits the (hidden) password fields as empty strings
        // even when the account is not created. This must not fail validation.
        $payload = $this->customer();
        $payload['password'] = '';
        $payload['password_confirmation'] = '';

        $this->post('/fr/checkout', $payload)->assertRedirect();

        $this->assertSame(1, Order::count());
    }

    public function test_checkout_requires_customer_details(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $response = $this->post('/fr/checkout', [
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => 'not-an-email',
            'address' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'email', 'address']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_with_an_empty_cart_redirects_to_the_cart(): void
    {
        $response = $this->post('/fr/checkout', $this->customer());

        $response->assertRedirect(route('shop.cart.show', ['locale' => 'fr']));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_double_submit_does_not_create_duplicate_orders(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $this->post('/fr/checkout', $this->customer());

        // Second submit: the cart is empty, so no new order is created.
        $this->post('/fr/checkout', $this->customer());

        $this->assertSame(1, Order::count());
    }

    public function test_a_guest_confirmation_page_requires_the_token(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);
        $this->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();
        $path = '/fr/commande/'.$order->order_number.'/confirmation';

        $this->get($path)->assertForbidden();
        $this->get($path.'?token='.$order->public_token)->assertOk()->assertSee($order->order_number, false);
    }

    public function test_an_order_owner_can_view_the_confirmation_without_a_token(): void
    {
        $user = User::factory()->create();
        $product = $this->product('smartphone-x-pro');

        $this->actingAs($user);
        $this->addCart($product->id, null, 1);

        $this->actingAs($user)->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();

        $this->actingAs($user)
            ->get('/fr/commande/'.$order->order_number.'/confirmation')
            ->assertOk();
    }

    public function test_another_user_cannot_view_someone_elses_confirmation(): void
    {
        $owner = User::factory()->create();
        $product = $this->product('smartphone-x-pro');

        $this->actingAs($owner);
        $this->addCart($product->id, null, 1);

        $this->actingAs($owner)->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();

        $other = User::factory()->create();
        $this->actingAs($other)
            ->get('/fr/commande/'.$order->order_number.'/confirmation')
            ->assertForbidden();
    }

    public function test_the_checkout_page_uses_rtl_for_arabic(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $response = $this->get('/ar/checkout');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('إتمام الطلب', false);
    }

    public function test_the_checkout_page_renders_in_french(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $response = $this->get('/fr/checkout');

        $response->assertOk();
        $response->assertSee('Informations de contact', false);
        $response->assertSee('Confirmer la commande', false);
    }

    public function test_admins_are_notified_when_an_order_is_created(): void
    {
        Notification::fake();

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::SuperAdmin->value);

        $product = $this->product('smartphone-x-pro');
        $this->addCart($product->id, null, 1);

        $this->post('/fr/checkout', $this->customer());

        Notification::assertSentTo($admin, NewOrderNotification::class);
    }
}
