<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates customer orders atomically: re-validates every line against the
 * catalog and stock under row locks, snapshots product data, decrements stock
 * and clears the cart inside a single database transaction. The browser can
 * never influence amounts — unit prices and totals are always recomputed
 * server-side from the current catalog.
 */
class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly StockService $stock,
    ) {}

    /**
     * @param  array<string, mixed>  $customer  validated customer snapshot fields
     * @param  array<string, mixed>  $cart  normalized cart (see CartService)
     */
    public function createOrder(array $customer, array $cart, ?int $userId = null): Order
    {
        $order = DB::transaction(function () use ($customer, $cart, $userId): Order {
            $orderNumber = $this->generateOrderNumber();

            $lines = [];
            $decrements = [];
            $subtotalCents = 0;
            $taxCents = 0;
            $pricing = app(PricingService::class);

            foreach ($cart['items'] as $item) {
                $productId = (int) $item['product_id'];
                $variantId = filled($item['variant_id'] ?? null) ? (int) $item['variant_id'] : null;

                $product = Product::withTrashed()->whereKey($productId)->lockForUpdate()->first();

                if (! $product || $product->trashed() || ! $product->status->isVisiblePublicly()) {
                    throw ValidationException::withMessages([
                        'items' => __('checkout.errors.product_unavailable'),
                    ]);
                }

                $variant = null;
                if ($product->isVariable()) {
                    $variant = ProductVariant::withTrashed()->whereKey($variantId)->lockForUpdate()->first();
                    if (! $variant || (int) $variant->product_id !== $productId || $variant->trashed()) {
                        throw ValidationException::withMessages(['items' => __('checkout.errors.variant_unavailable')]);
                    }
                } elseif (filled($variantId)) {
                    throw ValidationException::withMessages(['items' => __('checkout.errors.variant_unavailable')]);
                }

                $quantity = max(1, (int) $item['quantity']);
                $available = $this->cart->availableQuantity($product, $variant);

                if ($available !== null && $quantity > $available) {
                    $name = (string) ($item['name'] ?? $product->translatedName());

                    throw ValidationException::withMessages([
                        'items' => __('checkout.errors.stock_changed', ['name' => $name, 'available' => $available]),
                    ]);
                }

                $baseUnitCents = $this->toCents($this->cart->resolvePrice($product, $variant));
                $grossUnit = $pricing->grossPrice($baseUnitCents / 100);
                $unitPriceCents = $this->toCents($grossUnit);
                $lineTotalCents = $unitPriceCents * $quantity;
                $lineTaxCents = $this->toCents($pricing->taxAmountFromGross($grossUnit)) * $quantity;
                $subtotalCents += $lineTotalCents;
                $taxCents += $lineTaxCents;

                $unitName = $product->translatedName();
                $variantName = $item['variant_label'] ?? null;
                $sku = $variant?->sku ?: $product->sku;

                $lines[] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'product_name' => $unitName,
                    'variant_name' => $variantName,
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceCents,
                    'discount_amount' => 0,
                    'tax_amount' => $lineTaxCents,
                    'line_total' => $lineTotalCents,
                    'brand_id' => (int) $product->brand_id,
                    'product_snapshot' => [
                        'name' => $unitName,
                        'variant_name' => $variantName,
                        'sku' => $sku,
                        'unit_price' => $unitPriceCents,
                        'quantity' => $quantity,
                        'image' => $this->imageFor($product, $variant),
                    ],
                ];

                $managed = $product->isVariable() ? (bool) $variant->manage_stock : (bool) $product->manage_stock;
                if ($managed) {
                    $decrements[] = [$variant ?? $product, $product, $variant, $quantity];
                }
            }

            $shippingCents = $this->toCents((float) ($cart['totals']['shipping'] ?? 0));

            // Server-side discounts (automatic rules + applied coupon). Only the
            // aggregate applies here; promotions already altered each line price.
            $discount = app(DiscountService::class)->calculateForCart(
                [
                    'items' => $lines,
                    'subtotal' => $subtotalCents / 100,
                    'count' => array_sum(array_column($lines, 'quantity')),
                    'coupon_code' => $cart['coupon_code'] ?? null,
                ],
                $userId
            );

            $discountCents = $this->toCents($discount->total);
            $totalCents = max(0, $subtotalCents - $discountCents + $shippingCents);

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'currency' => (string) ($cart['currency'] ?? 'TND'),
                'subtotal' => $subtotalCents,
                'discount' => $discountCents,
                'shipping_amount' => $shippingCents,
                'tax_amount' => $taxCents,
                'total' => $totalCents,
                'customer_first_name' => $customer['first_name'],
                'customer_last_name' => $customer['last_name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'shipping_address' => $customer['address'],
                'shipping_city' => $customer['city'] ?? null,
                'shipping_postal_code' => $customer['postal_code'] ?? null,
                'shipping_country' => $customer['country'] ?? null,
                'customer_notes' => $customer['notes'] ?? null,
                'public_token' => Str::random(40),
            ]);

            $order->items()->createMany($lines);

            if (! $discount->isEmpty()) {
                $this->persistDiscounts($order, $discount);
            }

            foreach ($decrements as [$subject, $product, $variant, $quantity]) {
                $this->stock->decrease($subject, $quantity, [
                    'reference' => $orderNumber,
                    'reason' => __('checkout.stock_reason', ['order' => $orderNumber]),
                    'user_id' => $userId,
                ]);
            }

            return $order;
        });

        $this->notifyAdmins($order);

        return $order;
    }

    /**
     * Alert administrators that a new order has been placed.
     */
    private function notifyAdmins(Order $order): void
    {
        try {
            app(AdminNotificationService::class)->notify(NotificationType::OrderCreated, $order);
        } catch (\Throwable) {
            // notifications must never break the checkout
        }
    }

    /**
     * Record every applied discount against the order and bump coupon usage,
     * keeping a durable audit trail of what was granted.
     */
    private function persistDiscounts(Order $order, \App\Support\DiscountResult $discount): void
    {
        foreach ($discount->items as $line) {
            if (($line['kind'] ?? null) === 'shipping') {
                continue;
            }

            $order->discounts()->create([
                'discountable_type' => $line['discountable_type'] ?? null,
                'discountable_id' => $line['discountable_id'] ?? null,
                'kind' => $line['kind'] ?? 'rule',
                'code' => $line['code'] ?? null,
                'name' => $line['name'] ?? null,
                'type' => $line['type'] ?? 'percentage',
                'value' => $line['value'] ?? 0,
                'amount' => $this->toCents($line['amount'] ?? 0),
            ]);

            if (($line['kind'] ?? null) === 'coupon' && $line['discountable_id'] ?? null) {
                \App\Models\Coupon::query()->whereKey($line['discountable_id'])->increment('usage_count');
            }
        }
    }

    /**
     * Unique, human-friendly, non-predictable order number.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $number = 'CMD-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function toCents(float|int|string $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function imageFor(Product $product, ?ProductVariant $variant): ?string
    {
        if ($variant?->image) {
            return storefront_image($variant->image);
        }

        return $product->primaryImageUrl;
    }
}
