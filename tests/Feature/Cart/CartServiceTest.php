<?php

namespace Tests\Feature\Cart;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);
    }

    private function simpleProduct(string $frSlug): Product
    {
        return Product::query()
            ->with(['variants'])
            ->whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', $frSlug))
            ->firstOrFail();
    }

    private function smartphone(): Product
    {
        return $this->simpleProduct('smartphone-x-pro');
    }

    public function test_guest_cart_starts_empty(): void
    {
        $cart = app(CartService::class)->getCart();

        $this->assertTrue($cart['empty']);
        $this->assertSame(0, $cart['count']);
        $this->assertSame(0, $cart['line_count']);
        $this->assertSame(0.0, $cart['subtotal']);
    }

    public function test_add_adds_a_product_and_recomputes_totals(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 2);
        $cart = $service->getCart();

        $this->assertFalse($cart['empty']);
        $this->assertSame(2, $cart['count']);
        $this->assertCount(1, $cart['items']);
        $this->assertSame((float) 2499.00, $cart['items'][0]['unit_price']);
        $this->assertSame((float) 4998.00, $cart['items'][0]['line_total']);
        $this->assertSame((float) 4998.00, $cart['subtotal']);
        $this->assertTrue($service->has($product->id));
        $this->assertSame(2, $service->quantityOf($product->id));
    }

    public function test_adding_same_line_increments_quantity(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->add($product->id, null, 2);

        $this->assertSame(3, $service->quantityOf($product->id));
        $this->assertSame((float) 7497.00, $service->subtotal());
    }

    public function test_update_sets_quantity(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $cart = $service->update($product->id, null, 5);

        $this->assertSame(5, $service->quantityOf($product->id));
        $this->assertSame(5, $cart['count']);
    }

    public function test_update_to_zero_removes_the_line(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 2);
        $cart = $service->update($product->id, null, 0);

        $this->assertTrue($cart['empty']);
        $this->assertFalse($service->has($product->id));
    }

    public function test_update_clamps_quantity_to_available_stock(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $cart = $service->update($product->id, null, 500);

        $this->assertSame(42, $cart['items'][0]['quantity']);
        $this->assertSame(42, $service->quantityOf($product->id));
    }

    public function test_remove_drops_the_line(): void
    {
        $product = $this->smartphone();
        $product2 = $this->simpleProduct('ordinateur-portable-ultra');
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $service->add($product2->id, null, 1);
        $cart = $service->remove($product->id, null);

        $this->assertCount(1, $cart['items']);
        $this->assertSame($product2->id, $cart['items'][0]['product_id']);
    }

    public function test_clear_empties_the_cart(): void
    {
        $service = app(CartService::class);
        $service->add($this->smartphone()->id, null, 1);

        $service->clear();

        $this->assertTrue($service->getCart()['empty']);
        $this->assertSame(0, $service->count());
    }

    public function test_add_rejects_insufficient_stock(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $this->expectException(ValidationException::class);

        $service->add($product->id, null, 1000);
    }

    public function test_add_out_of_stock_product_is_rejected(): void
    {
        $product = $this->simpleProduct('t-shirt-classique');
        $service = app(CartService::class);

        $this->expectException(ValidationException::class);

        $service->add($product->id, null, 1);
    }

    public function test_add_draft_product_is_rejected(): void
    {
        $product = $this->smartphone();
        $product->update(['status' => ProductStatus::Draft->value]);
        $service = app(CartService::class);

        $this->expectException(HttpException::class);

        $service->add($product->id, null, 1);
    }

    public function test_variable_product_requires_a_valid_variant(): void
    {
        $product = $this->simpleProduct('pull-premium');
        $variant = $product->variants()->first();
        $service = app(CartService::class);

        $service->add($product->id, $variant->id, 1);

        $cart = $service->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertSame($variant->id, $cart['items'][0]['variant_id']);
        $this->assertSame((float) 129.00, $cart['items'][0]['unit_price']);
    }

    public function test_adding_a_variable_product_without_variant_is_rejected(): void
    {
        $product = $this->simpleProduct('pull-premium');
        $service = app(CartService::class);

        $this->expectException(HttpException::class);

        $service->add($product->id, null, 1);
    }

    public function test_price_changes_are_reflected_and_badged(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);

        $product->update(['price' => 2699.00]);

        $cart = $service->getCart();

        $this->assertSame((float) 2699.00, $cart['items'][0]['unit_price']);
        $this->assertTrue($cart['items'][0]['price_changed']);
        $this->assertSame((float) 2499.00, $cart['items'][0]['old_price']);
    }

    public function test_refresh_drops_a_product_that_became_inactive(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 1);
        $product->update(['status' => ProductStatus::Inactive->value]);

        $service->refresh();
        $cart = $service->getCart();

        $this->assertTrue($cart['empty']);
    }

    public function test_refresh_drops_a_line_whose_variant_was_deleted(): void
    {
        $product = $this->simpleProduct('pull-premium');
        $variant = $product->variants()->firstOrFail();
        $service = app(CartService::class);

        $service->add($product->id, $variant->id, 1);
        $variant->forceDelete();

        $service->refresh();
        $cart = $service->getCart();

        $this->assertTrue($cart['empty']);
    }

    public function test_guest_cart_persists_in_session(): void
    {
        $product = $this->smartphone();
        $service = app(CartService::class);

        $service->add($product->id, null, 3);

        $fresh = app(CartService::class)->getCart();

        $this->assertSame(3, $fresh['count']);
        $this->assertSame((float) 7497.00, $fresh['subtotal']);
    }

    public function test_authenticated_user_cart_is_stored_in_database(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->smartphone();
        $service = app(CartService::class);
        $service->add($product->id, null, 2);

        $fresh = app(CartService::class)->getCart();
        $this->assertSame(2, $fresh['count']);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $product = $this->smartphone();
        $guest = app(CartService::class);
        $guest->add($product->id, null, 2);

        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = app(CartService::class)->merge();

        $this->assertSame(2, $cart['count']);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertTrue(app(CartService::class)->getCart(false)['empty'] === false);
    }

    public function test_merge_respects_stock_limit(): void
    {
        $product = $this->smartphone();

        Session::put(CartService::SESSION_KEY, ['items' => [
            $product->id.':' => [
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 100,
                'unit_price' => 2499,
            ],
        ]]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = app(CartService::class)->merge();

        $this->assertSame(42, $cart['items'][0]['quantity']);
    }

    public function test_merge_keeps_existing_user_lines(): void
    {
        $product = $this->smartphone();
        $product2 = $this->simpleProduct('ordinateur-portable-ultra');

        app(CartService::class)->add($product2->id, null, 1);

        $user = User::factory()->create();
        $this->actingAs($user);
        app(CartService::class)->add($product->id, null, 1);

        $cart = app(CartService::class)->merge();

        $this->assertSame(2, $cart['line_count']);
    }
}
