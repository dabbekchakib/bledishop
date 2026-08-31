<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;

class NewsletterService
{
    /**
     * Whether the newsletter feature is enabled and a frontend form may be shown.
     */
    public function enabled(): bool
    {
        return (bool) setting('newsletter.enabled', false);
    }

    /**
     * Subscribe an email (optionally a name). Idempotent: an existing active
     * subscriber is kept, an unsubscribed one is re-activated, and a new one is
     * created. Never creates duplicates.
     *
     * @return array{success: bool, already: bool, message: string}
     */
    public function subscribe(string $email, ?string $name = null, string $source = 'footer'): array
    {
        $email = strtolower(trim($email));

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber !== null && $subscriber->isActive()) {
            return [
                'success' => false,
                'already' => true,
                'message' => __('shop.newsletter.already'),
            ];
        }

        if ($subscriber !== null) {
            $subscriber->reactivate($name);
            $subscriber->forceFill(['source' => $source])->save();

            return [
                'success' => true,
                'already' => false,
                'message' => __('shop.newsletter.success'),
            ];
        }

        $token = Str::random(64);

        NewsletterSubscriber::create([
            'email' => $email,
            'name' => filled($name) ? $name : null,
            'active' => true,
            'source' => $source,
            'token' => $token,
            'subscribed_at' => now(),
        ]);

        app(AdminNotificationService::class)->notify(NotificationType::NewsletterSubscribed, null, ['email' => $email]);

        return [
            'success' => true,
            'already' => false,
            'message' => __('shop.newsletter.success'),
        ];
    }

    /**
     * Unsubscribe by token. Returns an array used by the controller to render
     * the appropriate result.
     *
     * @return array{success: bool, already: bool, message: string}
     */
    public function unsubscribe(string $token): array
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if ($subscriber === null) {
            return [
                'success' => false,
                'already' => false,
                'message' => __('shop.newsletter.invalid_link'),
            ];
        }

        if (! $subscriber->isActive()) {
            return [
                'success' => true,
                'already' => true,
                'message' => __('shop.newsletter.already_unsubscribed'),
            ];
        }

        $subscriber->forceFill([
            'active' => false,
            'unsubscribed_at' => now(),
        ])->save();

        return [
            'success' => true,
            'already' => false,
            'message' => __('shop.newsletter.unsubscribed'),
        ];
    }
}
