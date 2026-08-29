<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Customer-facing database notification sent when an order reaches a status
 * the customer cares about (confirmed / shipped / delivered / cancelled).
 */
class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $locale = property_exists($notifiable, 'locale') && filled($notifiable->locale)
            ? $notifiable->locale
            : app()->getLocale();

        return [
            'type' => 'order.status_changed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status->value,
            'status_label' => $this->order->status->label(),
            'message' => __('account.order_status_changed', [
                'order' => $this->order->order_number,
                'status' => $this->order->status->label(),
            ], $locale),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
