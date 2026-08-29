<?php

namespace App\Services;

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
        $orderNumber = $this->generateOrderNumber();

        return DB::transaction(function () use ($customer, $cart, $userId, $orderNumber): Order {
            $lines = [];
            $decrements = [];
            $subtotalCents = 0;

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

                $unitPriceCents = $this->toCents($this->cart->resolvePrice($product, $variant));
                $lineTotalCents = $unitPriceCents * $quantity;
                $subtotalCents += $lineTotalCents;

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
                    'tax_amount' => 0,
                    'line_total' => $lineTotalCents,
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

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'currency' => (string) ($cart['currency'] ?? 'TND'),
                'subtotal' => $subtotalCents,
                'discount' => 0,
                'shipping_amount' => 0,
                'tax_amount' => 0,
                'total' => $subtotalCents,
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

            foreach ($decrements as [$subject, $product, $variant, $quantity]) {
                $this->stock->decrease($subject, $quantity, [
                    'reference' => $orderNumber,
                    'reason' => __('checkout.stock_reason', ['order' => $orderNumber]),
                    'user_id' => $userId,
                ]);
            }

            return $order;
        });
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
