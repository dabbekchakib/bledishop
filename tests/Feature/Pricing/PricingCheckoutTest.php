<?php

namespace Tests\Feature\Pricing;

use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\SettingsService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    private CartService $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->settings = app(SettingsService::class);
        $this->cart = app(CartService::class);
    }

    private function smartphone(): Product
    {
        return Product::query()
            ->whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 'smartphone-x-pro'))
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
        ];
    }

    public function test_cart_totals_include_tax_and_shipping_when_enabled(): void
    {
        $this->settings->set('tax.enabled', true);
        $this->settings->set('tax.included_in_price', false);
        $this->settings->set('tax.rate', 19);
        $this->settings->set('shipping.enabled', true);
        $this->settings->set('shipping.default_cost', 9.5);
        $this->settings->set('shipping.free_shipping_enabled', false);

        $product = $this->smartphone();
        // base 2499.00 → gross 2973.81 (incl. 474.81 tax), shipping 9.50
        $response = $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $cart = $this->cart->getCart();
        $this->assertSame(2973.81, round((float) $cart['subtotal'], 2));

        $this->get('/fr/cart')->assertOk()->assertSee('TVA', false)->assertSee('Livraison', false);
    }

    public function test_order_stores_tax_and_shipping_amounts(): void
    {
        $this->settings->set('tax.enabled', true);
        $this->settings->set('tax.included_in_price', false);
        $this->settings->set('tax.rate', 19);
        $this->settings->set('shipping.enabled', true);
        $this->settings->set('shipping.default_cost', 9.5);
        $this->settings->set('shipping.free_shipping_enabled', false);

        $product = $this->smartphone();
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/fr/checkout', $this->customer());

        $order = Order::latest('id')->firstOrFail();

        // base 2499.00 → gross 2973.81 (tax 474.81) + shipping 9.50
        $this->assertSame(297381, $order->subtotal);
        $this->assertSame(47481, $order->tax_amount);
        $this->assertSame(950, $order->shipping_amount);
        $this->assertSame(298331, $order->total);

        $this->assertSame(297381, $order->items()->first()->unit_price);
        $this->assertSame(47481, $order->items()->first()->tax_amount);
    }

    public function test_free_shipping_is_applied_above_the_threshold(): void
    {
        $this->settings->set('shipping.enabled', true);
        $this->settings->set('shipping.default_cost', 9.5);
        $this->settings->set('shipping.free_shipping_enabled', true);
        $this->settings->set('shipping.free_shipping_threshold', 2500);

        // 2 x 2499.00 = 4998.00 subtotal, well above the 2500 threshold
        $product = $this->smartphone();
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'quantity' => 2]);

        $cart = $this->cart->getCart();
        $this->assertSame(0.0, round((float) $cart['totals']['shipping'], 2));

        $this->post('/fr/checkout', $this->customer());
        $order = Order::latest('id')->firstOrFail();

        $this->assertSame(0, $order->shipping_amount);
        $this->assertSame(499800, $order->subtotal);
        $this->assertSame(499800, $order->total);
    }
}
