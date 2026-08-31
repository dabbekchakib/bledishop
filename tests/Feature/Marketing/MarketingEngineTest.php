<?php

namespace Tests\Feature\Marketing;

use App\Enums\BannerPosition;
use App\Enums\ProductStatus;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\CartService;
use App\Services\PromotionService;
use App\Services\SettingsService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);

        app(PromotionService::class)->forgetCache();
    }

    private function smartphone(): Product
    {
        return Product::query()
            ->whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 'smartphone-x-pro'))
            ->firstOrFail();
    }

    private function enablePromotions(): void
    {
        app(SettingsService::class)->set('marketing.enabled', true);
        app(PromotionService::class)->forgetCache();
    }

    public function test_guest_cart_starts_without_coupon(): void
    {
        $cart = app(CartService::class)->getCart();

        $this->assertEmpty($cart['items']);
        $this->assertSame(0.0, $cart['discount_total']);
        $this->assertNull($cart['coupon_code']);
    }

    public function test_applying_a_percentage_coupon_discounts_the_cart(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SALE10']);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('SALE10');

        $cart = $service->getCart();

        $this->assertSame('SALE10', $cart['coupon_code']);
        $this->assertSame('SALE10', ($cart['applied_coupon'] ?? [])['code'] ?? null);
        $this->assertEqualsWithDelta(249.9, $cart['discount_total'], 0.001);
        $this->assertEqualsWithDelta(2499.0, $cart['subtotal'], 0.001);

        $coupons = array_values(array_filter($cart['discounts'], fn (array $d): bool => $d['kind'] === 'coupon'));
        $this->assertCount(1, $coupons);
        $this->assertSame('coupon', $coupons[0]['kind']);
    }

    public function test_invalid_coupon_records_an_error_and_no_discount(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('DOES-NOT-EXIST');

        $cart = $service->getCart();

        $this->assertContains('marketing.coupon.invalid', $cart['discount_errors']);
        $this->assertSame(0.0, $cart['discount_total']);
        $this->assertNull($cart['applied_coupon']);
    }

    public function test_removing_a_coupon_restores_full_price(): void
    {
        Coupon::factory()->percentage(10)->create(['code' => 'SALE10']);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('SALE10');

        $this->assertSame(249.9, round($service->getCart()['discount_total'], 1));

        $service->removeCoupon();

        $cart = $service->getCart();
        $this->assertNull($cart['coupon_code']);
        $this->assertSame(0.0, $cart['discount_total']);
        $this->assertEqualsWithDelta(2499.0, $cart['total'], 0.001);
    }

    public function test_inactive_coupon_is_rejected(): void
    {
        Coupon::factory()->percentage(10)->inactive()->create(['code' => 'OFF']);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('OFF');

        $cart = $service->getCart();
        $this->assertContains('marketing.coupon.invalid', $cart['discount_errors']);
        $this->assertSame(0.0, $cart['discount_total']);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        Coupon::factory()->percentage(10)->expired()->create(['code' => 'OLDOFF']);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('OLDOFF');

        $cart = $service->getCart();
        $this->assertContains('marketing.coupon.invalid', $cart['discount_errors']);
    }

    public function test_min_subtotal_not_reached_rejects_coupon(): void
    {
        Coupon::factory()->percentage(10)->create([
            'code' => 'MIN100',
            'min_subtotal' => 10000, // requires a 10 000 HT subtotal
        ]);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1); // subtotal 2499

        $service->setCouponCode('MIN100');

        $cart = $service->getCart();
        $this->assertContains('marketing.coupon.min_subtotal', $cart['discount_errors']);
        $this->assertSame(0.0, $cart['discount_total']);
    }

    public function test_automatic_discount_rule_applies_to_cart(): void
    {
        \App\Models\DiscountRule::factory()->percentage(10)->create();

        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);

        $cart = $service->getCart();

        $this->assertEqualsWithDelta(249.9, $cart['discount_total'], 0.001);
        $kinds = array_column($cart['discounts'], 'kind');
        $this->assertContains('rule', $kinds);
        $this->assertNull($cart['coupon_code']);
    }

    public function test_only_the_best_rule_value_wins(): void
    {
        \App\Models\DiscountRule::factory()->percentage(5)->create(['priority' => 10]);
        \App\Models\DiscountRule::factory()->percentage(20)->create(['priority' => 20]);

        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);

        $cart = $service->getCart();
        $this->assertEqualsWithDelta(499.8, $cart['discount_total'], 0.001);
    }

    public function test_promotion_reduces_the_cart_unit_price(): void
    {
        $this->enablePromotions();

        $product = $this->smartphone();
        Promotion::factory()->percentage(10)->create([
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);

        $service = app(CartService::class);
        $service->add($product->id, null, 1);

        $cart = $service->getCart();

        $this->assertEqualsWithDelta(2249.1, $cart['items'][0]['unit_price'], 0.001);
        $this->assertEqualsWithDelta(2249.1, $cart['subtotal'], 0.001);
    }

    public function test_disabled_promotion_does_not_reduce_price(): void
    {
        $this->enablePromotions();

        $product = $this->smartphone();
        Promotion::factory()->percentage(10)->disabled()->create();

        $service = app(CartService::class);
        $service->add($product->id, null, 1);

        $cart = $service->getCart();
        $this->assertEqualsWithDelta(2499.0, $cart['items'][0]['unit_price'], 0.001);
    }

    public function test_promotions_gated_by_marketing_enabled_setting(): void
    {
        $product = $this->smartphone();
        Promotion::factory()->percentage(10)->create();

        // marketing.enabled is left false (SettingsSeeder default)
        $service = app(CartService::class);
        $service->add($product->id, null, 1);

        $cart = $service->getCart();
        $this->assertEqualsWithDelta(2499.0, $cart['items'][0]['unit_price'], 0.001);
    }

    public function test_checkout_with_coupon_persists_order_discount_and_bumps_usage(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'WELCOME', 'usage_limit' => null]);
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->setCouponCode('WELCOME');

        $this->post('/fr/checkout', [
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Salah',
            'phone' => '+216 98 000 000',
            'email' => 'ahmed@example.com',
            'address' => '12 Rue de la Liberté',
            'city' => 'Tunis',
            'postal_code' => '1001',
            'country' => 'Tunisie',
            'notes' => null,
        ])->assertRedirect();

        $order = Order::latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame(249900, $order->subtotal);
        $this->assertSame(24990, $order->discount);
        $this->assertSame(224910, $order->total);

        $line = OrderDiscount::query()->where('order_id', $order->id)->where('kind', 'coupon')->first();
        $this->assertNotNull($line);
        $this->assertSame('WELCOME', $line->code);
        $this->assertSame(24990, $line->amount);

        $this->assertSame(1, $coupon->fresh()->usage_count);
    }

    public function test_cart_http_coupon_apply_and_remove_endpoints(): void
    {
        Coupon::factory()->percentage(10)->create(['code' => 'HTTP10']);
        $product = $this->smartphone();

        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $apply = $this->postJson('/fr/cart/coupon', ['code' => 'HTTP10']);
        $apply->assertOk()->assertJson(['success' => true, 'coupon_code' => 'HTTP10']);

        $this->assertSame('HTTP10', app(CartService::class)->getCart()['coupon_code']);

        $remove = $this->deleteJson('/fr/cart/coupon');
        $remove->assertOk()->assertJson(['success' => true]);
        $this->assertNull(app(CartService::class)->getCart()['coupon_code']);
    }

    public function test_storefront_renders_promo_bar_banners_and_promotion_badges(): void
    {
        $settings = app(SettingsService::class);
        $this->enablePromotions();
        $settings->set('marketing.promo_bar_enabled', true);
        $settings->set('marketing.promo_bar_text', ['fr' => 'Promo flash magasin']);
        $settings->set('marketing.promo_bar_link', '/fr/shop');

        Banner::create([
            'title' => 'Bannière accueil test',
            'image' => null,
            'description' => 'Description bannière',
            'link' => '/fr/shop',
            'button_label' => 'Découvrir',
            'position' => BannerPosition::Homepage->value,
            'sort_order' => 1,
            'active' => true,
        ]);

        $this->smartphone();

        Promotion::factory()->percentage(10)->create([
            'name' => 'Promo smartphone',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);

        $this->get('/fr')->assertOk()
            ->assertSee('Promo flash magasin', false)
            ->assertSee('Bannière accueil test', false);

        $this->get('/fr/shop')->assertOk()->assertSee('Promo flash magasin', false);

        $this->get('/fr/product/smartphone-x-pro')->assertOk()->assertSee('-10%', false);

        $this->get('/fr/cart')->assertOk();
    }
}
