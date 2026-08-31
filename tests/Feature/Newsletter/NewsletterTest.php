<?php

namespace Tests\Feature\Newsletter;

use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function enableNewsletter(): void
    {
        app(SettingsService::class)->set('newsletter.enabled', true);
    }

    public function test_a_guest_can_subscribe_via_json(): void
    {
        $this->enableNewsletter();

        $response = $this->postJson('/fr/newsletter/subscribe', [
            'email' => 'john@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'john@example.com',
            'active' => true,
            'source' => 'footer',
        ]);
    }

    public function test_subscription_is_idempotent_for_an_active_subscriber(): void
    {
        $this->enableNewsletter();

        $this->postJson('/fr/newsletter/subscribe', ['email' => 'john@example.com']);

        $response = $this->postJson('/fr/newsletter/subscribe', ['email' => 'john@example.com']);

        $response->assertOk()
            ->assertJson(['success' => false, 'already' => true]);

        $this->assertSame(1, \App\Models\NewsletterSubscriber::where('email', 'john@example.com')->count());
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->enableNewsletter();

        $this->postJson('/fr/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertStatus(422);
    }

    public function test_subscription_is_rejected_when_newsletter_is_disabled(): void
    {
        $response = $this->postJson('/fr/newsletter/subscribe', ['email' => 'john@example.com']);

        $response->assertOk()
            ->assertJson(['success' => false, 'type' => 'warning']);

        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'john@example.com']);
    }

    public function test_a_guest_can_unsubscribe_via_token(): void
    {
        $subscriber = \App\Models\NewsletterSubscriber::factory()->create();

        $response = $this->get('/fr/newsletter/unsubscribe/'.$subscriber->token);

        $response->assertOk();
        $this->assertStringContainsString(__('shop.newsletter.unsubscribed'), $response->getContent());

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $subscriber->id,
            'active' => false,
        ]);
    }

    public function test_unsubscribe_with_an_invalid_token_is_rejected(): void
    {
        $response = $this->get('/fr/newsletter/unsubscribe/not-a-token');

        $response->assertOk();
        $this->assertStringContainsString(__('shop.newsletter.invalid_link'), $response->getContent());
    }

    public function test_a_previous_subscriber_is_reactivated(): void
    {
        $this->enableNewsletter();

        $subscriber = \App\Models\NewsletterSubscriber::factory()->unsubscribed()->create([
            'email' => 'reactivate@example.com',
        ]);

        $this->postJson('/fr/newsletter/subscribe', ['email' => 'reactivate@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $subscriber->id,
            'active' => true,
        ]);
    }
}
