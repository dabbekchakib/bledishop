<?php

namespace Tests\Feature\Wishlist;

use App\Models\Product;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\WishlistService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function enableWishlist(): void
    {
        app(SettingsService::class)->set('shop.wishlist_enabled', true);
    }

    private function product(): Product
    {
        return Product::factory()->create(['price' => 49.99]);
    }

    public function test_a_guest_can_add_a_product_via_json(): void
    {
        $this->enableWishlist();
        $product = $this->product();

        $response = $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $response->assertOk()
            ->assertJson(['success' => true, 'in_wishlist' => true]);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => null,
            'session_id' => session()->get('wishlist_guest_key'),
        ]);
    }

    public function test_toggling_removes_the_item(): void
    {
        $this->enableWishlist();
        $product = $this->product();

        $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $response = $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $response->assertOk()->assertJson(['success' => true, 'in_wishlist' => false]);

        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_adding_the_same_product_once_does_not_duplicate(): void
    {
        $this->enableWishlist();
        $product = $this->product();

        $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $this->assertSame(1, \App\Models\WishlistItem::where('product_id', $product->id)->count());
    }

    public function test_wishlist_is_rejected_when_disabled(): void
    {
        $product = $this->product();

        $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['success' => false, 'type' => 'warning']);

        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_an_authenticated_user_wishlist_is_scoped_to_their_account(): void
    {
        $this->enableWishlist();
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/fr/wishlist/toggle', ['product_id' => $product->id])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_the_wishlist_page_lists_the_products(): void
    {
        $this->enableWishlist();
        $product = $this->product();

        $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $response = $this->get('/fr/wishlist');

        $response->assertOk();
        $response->assertSee(__('shop.wishlist.title'), false);
        $response->assertSee($product->translatedName(), false);
    }

    public function test_the_empty_wishlist_page_shows_the_empty_state(): void
    {
        $this->enableWishlist();

        $this->get('/fr/wishlist')
            ->assertOk()
            ->assertSee(__('shop.wishlist.empty_title'), false);
    }

    public function test_a_guest_wishlist_merges_into_the_account_on_login(): void
    {
        $this->enableWishlist();
        $product = $this->product();

        $this->postJson('/fr/wishlist/toggle', ['product_id' => $product->id]);

        $guestKey = session()->get('wishlist_guest_key');
        $this->assertNotNull($guestKey);

        $user = User::factory()->create();

        app(WishlistService::class)->mergeGuestToUser($guestKey, $user);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseMissing('wishlist_items', ['session_id' => $guestKey]);
    }
}
