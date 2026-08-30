<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centralized stock operations. Every modification goes through this service,
 * is wrapped in a database transaction and leaves a StockMovement trace.
 *
 * The subject is either a ProductVariant (for variable products) or a Product
 * (for simple products). Stock can never become negative.
 */
class StockService
{
    /**
     * Current available quantity for the given subject.
     */
    public function getAvailableStock(Model $subject): int
    {
        return $this->resolveQuantity($subject);
    }

    public function canSell(Model $subject, int $quantity = 1): bool
    {
        return $this->getAvailableStock($subject) >= max(1, $quantity);
    }

    public function isInStock(Model $subject): bool
    {
        return $this->stockStatusOf($subject) === StockStatus::InStock;
    }

    public function isOutOfStock(Model $subject): bool
    {
        return $this->getAvailableStock($subject) <= 0;
    }

    /**
     * A subject counts as "low stock" when stock is managed, not yet empty,
     * and below or equal to its configured threshold.
     */
    public function isLowStock(Model $subject): bool
    {
        if (! $this->isStockManaged($subject)) {
            return false;
        }

        $quantity = $this->getAvailableStock($subject);
        $threshold = $this->resolveThreshold($subject);

        return $quantity > 0 && $threshold > 0 && $quantity <= $threshold;
    }

    public function stockStatusOf(Model $subject): StockStatus
    {
        $status = $subject->stock_status;

        if ($status instanceof StockStatus) {
            return $status;
        }

        return $this->getAvailableStock($subject) > 0 ? StockStatus::InStock : StockStatus::OutOfStock;
    }

    /**
     * Record an initial stock (used e.g. when a product is created with stock).
     */
    public function initialize(Model $subject, int $quantity, array $extra = []): void
    {
        $before = $this->getAvailableStock($subject);
        $this->mutation(fn () => $this->applyInitial($subject, $quantity), $subject, $quantity, StockMovementType::Initial, $extra);
        $this->notifyAfterMutation($subject, $before);
    }

    public function increase(Model $subject, int $quantity, array $extra = []): void
    {
        $quantity = max(0, $quantity);

        if ($quantity === 0) {
            return;
        }

        $before = $this->getAvailableStock($subject);
        $this->mutation(fn () => $this->applyIncrease($subject, $quantity), $subject, $quantity, StockMovementType::Increase, $extra);
        $this->notifyAfterMutation($subject, $before);
    }

    /**
     * Decrease stock. Throws when the resulting quantity would be negative.
     */
    public function decrease(Model $subject, int $quantity, array $extra = []): void
    {
        $quantity = max(0, $quantity);

        if ($quantity === 0) {
            return;
        }

        $before = $this->getAvailableStock($subject);

        $this->mutation(function () use ($subject, $quantity): void {
            $current = $this->resolveQuantity($subject);

            if ($current - $quantity < 0) {
                throw ValidationException::withMessages([
                    'stock' => 'Stock insuffisant pour cette opération.',
                ]);
            }

            $this->persistQuantity($subject, $current - $quantity);
        }, $subject, -$quantity, StockMovementType::Decrease, $extra);

        $this->notifyAfterMutation($subject, $before);
    }

    /**
     * Set the stock to an absolute value, recording the delta as an adjustment.
     */
    public function adjust(Model $subject, int $newQuantity, array $extra = []): void
    {
        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Le stock ne peut pas être négatif.',
            ]);
        }

        $current = $this->resolveQuantity($subject);
        $delta = $newQuantity - $current;

        $this->mutation(function () use ($subject, $newQuantity): void {
            $this->persistQuantity($subject, $newQuantity);
        }, $subject, $delta, StockMovementType::Adjustment, $extra);

        $this->notifyAfterMutation($subject, $current);
    }

    /**
     * Fire the relevant stock alert (out of stock / low stock / restocked)
     * after a stock mutation, de-duplicated via the AdminNotificationService.
     */
    private function notifyAfterMutation(Model $subject, int $before): void
    {
        if (! $this->isStockManaged($subject)) {
            return;
        }

        $available = $this->getAvailableStock($subject);
        $threshold = $this->resolveThreshold($subject);

        if ($available <= 0) {
            $this->notify(NotificationType::OutOfStock, $subject);

            return;
        }

        if ($threshold > 0 && $available <= $threshold) {
            $this->notify(NotificationType::LowStock, $subject);

            return;
        }

        if ($threshold > 0 && $before <= $threshold && $available > $threshold) {
            $this->notify(NotificationType::StockRestocked, $subject);
        }
    }

    private function notify(NotificationType $type, Model $subject): void
    {
        try {
            app(AdminNotificationService::class)->notify($type, $subject);
        } catch (\Throwable) {
            // stock operations must never be interrupted by notifications
        }
    }

    private function mutation(\Closure $operation, Model $subject, int $movementQuantity, StockMovementType $type, array $extra): void
    {
        DB::transaction(function () use ($operation, $subject, $movementQuantity, $type, $extra): void {
            $operation();

            $this->recordMovement($subject, $type, $movementQuantity, $extra);
        });
    }

    private function applyInitial(Model $subject, int $quantity): void
    {
        $current = $this->resolveQuantity($subject);
        $this->persistQuantity($subject, $current + $quantity);
    }

    private function applyIncrease(Model $subject, int $quantity): void
    {
        $current = $this->resolveQuantity($subject);
        $this->persistQuantity($subject, $current + $quantity);
    }

    private function recordMovement(Model $subject, StockMovementType $type, int $quantity, array $extra): void
    {
        $payload = [
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $extra['reference'] ?? null,
            'reason' => $extra['reason'] ?? null,
            'notes' => $extra['notes'] ?? null,
        ];

        if ($subject instanceof ProductVariant) {
            $payload['product_id'] = $subject->product_id;
            $payload['product_variant_id'] = $subject->id;
        } else {
            $payload['product_id'] = $subject->id;
        }

        if (! empty($extra['user_id'])) {
            $payload['user_id'] = $extra['user_id'];
        }

        StockMovement::create($payload);
    }

    private function resolveQuantity(Model $subject): int
    {
        return (int) $subject->stock_quantity;
    }

    private function resolveThreshold(Model $subject): int
    {
        return (int) $subject->low_stock_threshold;
    }

    private function isStockManaged(Model $subject): bool
    {
        return (bool) $subject->manage_stock;
    }

    private function persistQuantity(Model $subject, int $quantity): void
    {
        $subject->forceFill(['stock_quantity' => $quantity])->save();
    }
}
