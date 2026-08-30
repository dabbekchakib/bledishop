<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centralized, audited status workflow for orders.
 *
 * Every important transition goes through this service so that the change is:
 *   - validated against the allowed transitions,
 *   - recorded in the status history,
 *   - reflected on the derived timestamps (confirmed_at / completed_at),
 *   - consistently handled for stock (restored exactly once on cancellation),
 *   - notified to the customer when relevant.
 *
 * Created orders already decrement their stock at creation (see OrderService),
 * so the admin workflow never decrements again. On cancellation the consumed
 * stock is restored, idempotently (guarded by orders.stock_restored_at).
 */
class OrderStatusService
{
    /**
     * @var array<string, array<int, string>> allowed transitions (from => allowed targets)
     */
    private const ALLOWED_TRANSITIONS = [
        OrderStatus::Pending->value => [OrderStatus::Confirmed->value, OrderStatus::Cancelled->value],
        OrderStatus::Confirmed->value => [OrderStatus::Processing->value, OrderStatus::Cancelled->value],
        OrderStatus::Processing->value => [OrderStatus::Shipped->value, OrderStatus::Cancelled->value],
        OrderStatus::Shipped->value => [OrderStatus::Delivered->value, OrderStatus::Cancelled->value],
        OrderStatus::Delivered->value => [],
        OrderStatus::Cancelled->value => [],
        OrderStatus::OnHold->value => [OrderStatus::Pending->value, OrderStatus::Confirmed->value, OrderStatus::Cancelled->value],
    ];

    public function __construct(private readonly StockService $stock) {}

    /**
     * List the statuses to which the given order may move.
     *
     * @return array<string, string> value => label
     */
    public function allowedTargets(Order $order): array
    {
        return collect(self::ALLOWED_TRANSITIONS[$order->status->value] ?? [])
            ->mapWithKeys(fn (string $value): array => [$value => OrderStatus::tryFrom($value)?->label() ?? $value])
            ->all();
    }

    /**
     * Transition an order to a new status, recording the history and applying
     * the side effects exactly once. No-op when the status does not change.
     *
     * @throws ValidationException when the transition is not allowed
     */
    public function transition(Order $order, OrderStatus $target, ?User $changedBy = null, ?string $note = null): Order
    {
        $current = $order->status;

        if ($current === $target) {
            return $order; // idempotent no-op
        }

        $allowed = self::ALLOWED_TRANSITIONS[$current->value] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('admin.orders.invalid_transition', [
                    'from' => $current->label(),
                    'to' => $target->label(),
                ]),
            ]);
        }

        $oldStatus = $current->value;

        DB::transaction(function () use ($order, $target, $changedBy, $note, $oldStatus): void {
            $this->applySideEffects($order, $target);

            $order->status = $target;
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $target->value,
                'changed_by' => $changedBy?->id,
                'note' => filled($note) ? $note : null,
            ]);
        });

        $this->notifyCustomer($order);
        $this->notifyAdmins($order, $target);

        return $order->refresh();
    }

    /**
     * Apply stock / timestamp side effects for the target status.
     */
    private function applySideEffects(Order $order, OrderStatus $target): void
    {
        if ($target === OrderStatus::Confirmed && $order->confirmed_at === null) {
            $order->confirmed_at = now();
        }

        if ($target === OrderStatus::Delivered && $order->completed_at === null) {
            $order->completed_at = now();
        }

        if ($target === OrderStatus::Cancelled) {
            $this->restoreStockOnce($order);
        }
    }

    /**
     * Restore the stock consumed by the order exactly once. Cancellation is
     * idempotent thanks to the stock_restored_at guard.
     */
    private function restoreStockOnce(Order $order): void
    {
        if ($order->stockWasRestored()) {
            return;
        }

        foreach ($order->items as $item) {
            $subject = null;

            if (filled($item->product_variant_id)) {
                $subject = ProductVariant::withTrashed()->with('product')->find($item->product_variant_id);
            } elseif (filled($item->product_id)) {
                $subject = Product::withTrashed()->find($item->product_id);
            }

            if ($subject === null || ! (bool) $subject->manage_stock) {
                continue;
            }

            $this->stock->increase($subject, (int) $item->quantity, [
                'reference' => $order->order_number,
                'reason' => __('admin.orders.stock_restored', ['order' => $order->order_number]),
                'user_id' => $order->user_id,
            ]);
        }

        $order->markStockRestored();
    }

    private function notifyAdmins(Order $order, OrderStatus $target): void
    {
        try {
            app(AdminNotificationService::class)->notify(
                NotificationType::OrderStatusChanged,
                $order,
                ['status' => $target->label()],
            );
        } catch (\Throwable) {
            // notifications must never break the status workflow
        }
    }

    private function notifyCustomer(Order $order): void
    {
        if ($order->user_id === null) {
            return;
        }

        $notifiable = User::find($order->user_id);

        if ($notifiable === null) {
            return;
        }

        $statusesToNotify = [
            OrderStatus::Confirmed->value,
            OrderStatus::Shipped->value,
            OrderStatus::Delivered->value,
            OrderStatus::Cancelled->value,
        ];

        if (in_array($order->status->value, $statusesToNotify, true)) {
            $notifiable->notify(new OrderStatusNotification($order));
        }
    }
}
