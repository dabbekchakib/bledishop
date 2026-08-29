<?php

namespace Tests\Feature\Cart;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function test_guest_adds_a_product_via_json(): void
    {
        $product = $this->product('smartphone-x-pro');

        $response = $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart_count' => 2,
                'item_count' => 1,
            ])
            ->assertJsonPath('subtotal', 4998);
    }

    public function test_guest_updates_a_cart_line_via_json(): void
    {
        $product = $this->product('smartphone-x-pro');

        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->patchJson('/fr/cart/items/'.$product->id.':', ['quantity' => 4]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart_count' => 4,
                'line_quantity' => 4,
                'line_removed' => false,
            ]);
    }

    public function test_guest_removes_a_cart_line_via_json(): void
    {
        $product = $this->product('smartphone-x-pro');

        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->deleteJson('/fr/cart/items/'.$product->id.':');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart_count' => 0,
                'item_count' => 0,
                'empty' => true,
            ]);
    }

    public function test_guest_clears_the_cart_via_json(): void
    {
        $product = $this->product('smartphone-x-pro');

        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->deleteJson('/fr/cart');

        $response->assertOk()->assertJson(['success' => true, 'cart_count' => 0, 'empty' => true]);
    }

    public function test_adding_two_different_products_creates_two_lines(): void
    {
        $a = $this->product('smartphone-x-pro');
        $b = $this->product('ordinateur-portable-ultra');

        $this->postJson('/fr/cart/add', ['product_id' => $a->id, 'variant_id' => null, 'quantity' => 1]);
        $response = $this->postJson('/fr/cart/add', ['product_id' => $b->id, 'variant_id' => null, 'quantity' => 1]);

        $response->assertJson(['item_count' => 2, 'cart_count' => 2]);
    }

    public function test_adding_a_variable_product_variant_via_json(): void
    {
        $product = $this->product('pull-premium');
        $variant = $product->variants()->firstOrFail();

        $response = $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'item_count' => 1])
            ->assertJsonPath('subtotal', 129);
    }

    public function test_adding_invalid_variant_returns_an_error(): void
    {
        $product = $this->product('pull-premium');

        $response = $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => 999999,
            'quantity' => 1,
        ]);

        $response->assertJson(['success' => false]);
    }

    public function test_insufficient_stock_returns_a_warning(): void
    {
        $product = $this->product('smartphone-x-pro');

        $response = $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1000,
        ]);

        $response->assertJson(['success' => false, 'type' => 'warning']);
    }

    public function test_the_cart_page_lists_items_in_french(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->get('/fr/cart');

        $response->assertOk();
        $response->assertSee('Mon panier', false);
        $response->assertSee('Smartphone X Pro', false);
        $response->assertSee('Sous-total', false);
    }

    public function test_the_cart_page_works_in_english(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->get('/en/cart');

        $response->assertOk();
        $response->assertSee('My cart', false);
        $response->assertSee('Subtotal', false);
    }

    public function test_the_cart_page_uses_rtl_for_arabic(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->get('/ar/cart');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    public function test_the_drawer_endpoint_returns_drawer_markup(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);

        $response = $this->get('/fr/cart/drawer');

        $response->assertOk();
        $response->assertSee('Smartphone X Pro', false);
    }

    public function test_the_fragments_endpoint_returns_items_and_summary(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]);

        $response = $this->getJson('/fr/cart/fragments');

        $response->assertOk()->assertJson(['success' => true, 'cart_count' => 2]);
        $this->assertStringContainsString('Smartphone X Pro', $response->json('items_html'));
        $this->assertStringContainsString('Sous-total', $response->json('summary_html'));
    }

    public function test_an_empty_cart_page_shows_the_empty_state(): void
    {
        $response = $this->get('/fr/cart');

        $response->assertOk();
        $response->assertSee('Votre panier est vide', false);
        $response->assertSee('Continuer mes achats', false);
    }

    public function test_checkout_placeholder_redirects_to_cart(): void
    {
        $this->get('/fr/cart/checkout')
            ->assertRedirect(route('shop.cart.show', ['locale' => 'fr']))
            ->assertSessionHas('info');
    }

    public function test_an_authenticated_user_cart_is_persisted_in_the_database(): void
    {
        $user = User::factory()->create();
        $product = $this->product('smartphone-x-pro');

        $this->actingAs($user)->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_a_guest_cart_merges_after_login(): void
    {
        $product = $this->product('smartphone-x-pro');
        $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);
        event(new Login('web', $user, false));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->get('/fr/cart');
        $response->assertOk();
    }
}
