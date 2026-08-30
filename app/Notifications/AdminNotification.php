<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UrlRedirect;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Generic database notification for the administration centre.
 *
 * The behaviour of every admin notification (recipient permission, priority,
 * title/message keys, default URL and de-dup fingerprint) is declared on the
 * {@see NotificationType} enum. This class only takes care of rendering the
 * localized payload for each recipient, so adding a new notification type is a
 * matter of a new enum case (plus its translation keys) and firing it through
 * the AdminNotificationService - no parallel notification class is required.
 *
 * Deliberately synchronous (no ShouldQueue): these feed the live header badge
 * and must be persisted immediately, independent of any queue worker.
 */
class AdminNotification extends Notification
{
    public function __construct(
        public NotificationType $type,
        public mixed $subject = null,
        public array $params = [],
        ?string $locale = null,
    ) {
        $this->locale = $locale;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $locale = $this->locale ?? (property_exists($notifiable, 'locale') && filled($notifiable->locale)
            ? $notifiable->locale
            : app()->getLocale());

        $data = [
            'type' => $this->type->value,
            'priority' => $this->type->priority()->value,
            'title' => __($this->type->titleKey(), $this->resolveParams(), $locale),
            'message' => __($this->type->messageKey(), $this->resolveParams(), $locale),
            'action_url' => $this->type->url($this->subject),
        ];

        if ($this->subject instanceof Model) {
            $data['subject_type'] = $this->subject->getMorphClass();
            $data['subject_id'] = (string) $this->subject->getKey();
        }

        return $data;
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function dedupKey(): string
    {
        return $this->type->dedupKey($this->subject);
    }

    /**
     * Translation placeholders resolved from the related subject.
     *
     * @return array<string, string>
     */
    protected function resolveParams(): array
    {
        $params = $this->params;

        if ($this->subject instanceof Order) {
            $params['order'] ??= $this->subject->order_number;
            $params['customer'] ??= $this->subject->customerFullName();
        }

        if ($this->subject instanceof Product) {
            $params['product'] ??= $this->subject->translatedName();
            $params['sku'] ??= $this->subject->sku;
        }

        if ($this->subject instanceof ProductVariant) {
            $params['product'] ??= $this->subject->product?->translatedName();
            $params['sku'] ??= $this->subject->sku;
            $params['variant'] ??= $this->subject->combinationLabel();
        }

        if ($this->subject instanceof Category) {
            $params['name'] ??= $this->subject->translatedName();
        }

        if ($this->subject instanceof Brand) {
            $params['name'] ??= $this->subject->translatedName();
        }

        if ($this->subject instanceof User) {
            $params['name'] ??= $this->subject->fullName();
        }

        if ($this->subject instanceof Page) {
            $params['name'] ??= $this->subject->translatedTitle();
        }

        if ($this->subject instanceof UrlRedirect) {
            $params['from'] ??= $this->subject->source;
            $params['to'] ??= $this->subject->destination;
        }

        if ($this->subject instanceof Model) {
            $params['id'] ??= (string) $this->subject->getKey();
        }

        return $params;
    }
}
