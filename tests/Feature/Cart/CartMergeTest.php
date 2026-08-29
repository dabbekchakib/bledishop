<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function product(): Product
    {
        return Product::query()
            ->whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 'smartphone-x-pro'))
            ->firstOrFail();
    }

    public function test_a_guest_cart_is_merged_into_the_user_cart_on_login(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ]);

        $this->post('/fr/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);

        $line = $cart->items()->where('product_id', $product->id)->first();
        $this->assertNotNull($line);
        $this->assertSame(2, (int) $line->quantity);
    }

    public function test_guest_cart_is_merged_when_registering(): void
    {
        $product = $this->product();

        $this->postJson('/fr/cart/add', [
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
        ]);

        $this->post('/fr/register', [
            'name' => 'New Client',
            'email' => 'client@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'client@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertSame(1, (int) $cart->items()->where('product_id', $product->id)->value('quantity'));
    }
}
