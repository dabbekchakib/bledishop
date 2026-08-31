<?php

namespace Tests\Feature\Catalog;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ReviewService;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function enableReviews(): void
    {
        app(SettingsService::class)->set('shop.reviews_enabled', true);
    }

    private function reviewUrl(Product $product): string
    {
        $slug = $product->translation('fr')?->slug ?? $product->translatedSlug();

        return '/fr/product/'.$slug.'/reviews';
    }

    private function product(): Product
    {
        return Product::factory()->create(['price' => 199.99]);
    }

    public function test_an_authenticated_user_can_submit_a_review(): void
    {
        $this->enableReviews();
        $product = $this->product();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->reviewUrl($product), [
            'rating' => 5,
            'title' => 'Excellent',
            'comment' => 'Très satisfait du produit.',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_an_invalid_rating_is_rejected(): void
    {
        $this->enableReviews();
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson($this->reviewUrl($product), ['rating' => 9])
            ->assertStatus(422);
    }

    public function test_a_user_cannot_review_the_same_product_twice(): void
    {
        $this->enableReviews();
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson($this->reviewUrl($product), ['rating' => 4]);

        $response = $this->actingAs($user)->postJson($this->reviewUrl($product), ['rating' => 3]);

        $response->assertOk()->assertJson(['success' => false]);
        $this->assertSame(1, ProductReview::where('user_id', $user->id)->where('product_id', $product->id)->count());
    }

    public function test_reviews_are_rejected_when_the_feature_is_disabled(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson($this->reviewUrl($product), ['rating' => 5])
            ->assertOk()
            ->assertJson(['success' => false, 'type' => 'warning']);

        $this->assertDatabaseMissing('product_reviews', ['product_id' => $product->id]);
    }

    public function test_guests_can_review_when_allowed(): void
    {
        $this->enableReviews();
        app(SettingsService::class)->set('reviews.allow_guests', true);
        $product = $this->product();

        $this->postJson($this->reviewUrl($product), ['rating' => 4, 'comment' => 'Avis invité.'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => null,
        ]);
    }

    public function test_guests_cannot_review_when_disallowed(): void
    {
        $this->enableReviews();
        app(SettingsService::class)->set('reviews.allow_guests', false);
        $product = $this->product();

        $this->postJson($this->reviewUrl($product), ['rating' => 4])
            ->assertOk()
            ->assertJson(['success' => false, 'type' => 'warning']);
    }

    public function test_a_verified_purchase_is_flagged(): void
    {
        $this->enableReviews();
        $product = $this->product();
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'status' => OrderStatus::Delivered->value,
            'user_id' => $user->id,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $review = app(ReviewService::class)->submit($product->id, 5, null, null, $user);

        $this->assertTrue($review->verified_purchase);
    }

    public function test_only_approved_reviews_are_counted_in_stats(): void
    {
        $this->enableReviews();
        $product = $this->product();
        $user = User::factory()->create();
        $service = app(ReviewService::class);

        $pending = ProductReview::factory()->create(['product_id' => $product->id, 'user_id' => $user->id, 'rating' => 1]);
        $approved = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
        ]);

        $stats = $service->stats($product->id);

        $this->assertSame(1, $stats['count']);
        $this->assertSame(5.0, $stats['average']);
        $this->assertSame(0, $stats['distribution'][1]);
        $this->assertSame(1, $stats['distribution'][5]);
        $this->assertFalse($pending->isApproved());
        $this->assertTrue($approved->isApproved());
    }

    public function test_moderation_approves_and_invalidates_stats(): void
    {
        $product = $this->product();
        $service = app(ReviewService::class);
        $review = ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 3]);

        $service->moderate($review, ReviewStatus::Approved);

        $this->assertTrue($review->fresh()->isApproved());
        $this->assertNotNull($review->fresh()->approved_at);
        $this->assertSame(1, $service->stats($product->id)['count']);
    }

    public function test_pending_reviews_are_not_shown_on_the_product_page(): void
    {
        $this->enableReviews();
        $product = $this->product();
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 4, 'comment' => 'Avis en attente.']);

        $slug = $product->translation('fr')?->slug ?? $product->translatedSlug();

        $response = $this->get('/fr/product/'.$slug);

        $response->assertOk();
        $response->assertDontSee('Avis en attente.');
        $response->assertSee(__('shop.reviews.no_reviews_yet'), false);
    }
}
