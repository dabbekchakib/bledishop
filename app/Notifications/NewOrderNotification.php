<?php

namespace App\Notifications;

use App\Enums\NotificationPriority;
use App\Filament\Resources\OrdersResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
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
        return [
            'type' => 'order.created',
            'priority' => NotificationPriority::Info->value,
            'title' => __('admin.notifications.titles.order_created'),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer' => $this->order->customerFullName(),
            'total' => $this->order->totalAmount(),
            'message' => __('checkout.notification.new_order'),
            'action_url' => OrdersResource::getUrl('view', ['record' => $this->order]),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
