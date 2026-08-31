<?php

namespace App\Services;

use App\Enums\StockStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Centralized cart logic shared by every entry point (controllers, views,
 * AJAX). Two persistence strategies are supported transparently:
 *
 *  - guests: the cart lives in the Laravel session (ids only);
 *  - authenticated users: the cart is stored in the carts / cart_items tables
 *    so it survives across devices and session expiry.
 *
 * On sign-in the guest session cart is merged into the user cart when stock
 * allows. Prices and totals are always recomputed server-side: the browser can
 * only ever influence which product/variant/quantity is requested, never the
 * amounts. Adding to the cart never reserves or modifies stock.
 */
class CartService
{
    public const SESSION_KEY = 'cart';

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed> normalized, refreshed cart
     */
    public function getCart(bool $forceRefresh = true): array
    {
        $raw = $forceRefresh ? $this->refresh() : $this->rawItems();

        return $this->buildCart($raw ?: $this->rawItems());
    }

    /**
     * Normalized item list (see buildCart). Alias for views.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCartItems(): array
    {
        $cart = $this->getCart();

        return $cart['items'];
    }

    /**
     * The coupon code currently applied to the cart, if any.
     */
    public function getCouponCode(): ?string
    {
        if ($this->isAuthenticated()) {
            $code = $this->cartModel()->coupon_code;

            return $code ? (string) $code : null;
        }

        $cart = Session::get(self::SESSION_KEY);

        return isset($cart['coupon_code']) && filled($cart['coupon_code'])
            ? (string) $cart['coupon_code']
            : null;
    }

    public function setCouponCode(?string $code): void
    {
        $code = $code !== null ? strtoupper(trim($code)) : null;

        if ($this->isAuthenticated()) {
            $this->cartModel()->forceFill(['coupon_code' => $code])->save();

            return;
        }

        $cart = Session::get(self::SESSION_KEY);
        $cart = is_array($cart) ? $cart : [];
        $cart['coupon_code'] = $code;
        Session::put(self::SESSION_KEY, $cart);
    }

    public function removeCoupon(): void
    {
        $this->setCouponCode(null);
    }

    public function add(int $productId, ?int $variantId, int $quantity): array
    {
        $quantity = max(1, $quantity);

        $product = Product::withTrashed()->find($productId);
        abort_unless($product, 404, __('cart.errors.product_missing'));
        abort_if((bool) $product->trashed() || ! $product->status->isVisiblePublicly(), 422, __('cart.errors.unavailable'));

        $variant = null;

        if ($product->isVariable()) {
            $variant = ProductVariant::withTrashed()->find($variantId);
            abort_unless($variant && (int) $variant->product_id === (int) $productId, 422, __('cart.errors.variant_invalid'));
            abort_if((bool) $variant->trashed(), 422, __('cart.errors.variant_invalid'));
        } else {
            abort_if(filled($variantId), 422, __('cart.errors.variant_not_needed'));
        }

        $available = $this->availableQuantity($product, $variant);
        $requested = $this->currentQuantity($productId, $variantId) + $quantity;

        if ($available !== null && $requested > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.insufficient_stock', ['available' => $available]),
            ]);
        }

        $unitPrice = $this->resolvePrice($product, $variant);
        $this->store($productId, $variantId, function (array $items) use ($productId, $variantId, $quantity, $unitPrice): array {
            $key = $this->key($productId, $variantId);
            $items[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => (int) ($items[$key]['quantity'] ?? 0) + $quantity,
                'unit_price' => $unitPrice,
            ];

            return $items;
        });

        return $this->getCart();
    }

    public function update(int $productId, ?int $variantId, int $quantity): array
    {
        $key = $this->key($productId, $variantId);

        if (! $this->contains($productId, $variantId)) {
            throw new \InvalidArgumentException(__('cart.errors.item_missing'));
        }

        if ($quantity <= 0) {
            $this->remove($productId, $variantId);

            return $this->getCart();
        }

        $product = Product::withTrashed()->find($productId);
        $variant = $variantId !== null ? ProductVariant::withTrashed()->find($variantId) : null;

        $available = $product ? $this->availableQuantity($product, $variant) : 0;
        $clamped = ($available !== null && $quantity > $available) ? max(0, $available) : $quantity;

        if ($clamped === 0) {
            $this->remove($productId, $variantId);

            return $this->getCart();
        }

        $this->store($productId, $variantId, function (array $items) use ($key, $clamped) {
            $items[$key]['quantity'] = (int) $clamped;

            return $items;
        });

        return $this->getCart();
    }

    public function remove(int $productId, ?int $variantId): array
    {
        $key = $this->key($productId, $variantId);

        $this->store($productId, $variantId, function (array $items) use ($key) {
            unset($items[$key]);

            return $items;
        });

        return $this->getCart();
    }

    public function clear(): void
    {
        if ($this->isAuthenticated()) {
            $this->cartModel()->items()->delete();
        } else {
            Session::put(self::SESSION_KEY, []);
        }
    }

    public function count(): int
    {
        $raw = $this->rawItems();

        return (int) array_sum(array_column($raw, 'quantity'));
    }

    public function subtotal(): float
    {
        $cart = $this->getCart();

        return (float) $cart['subtotal'];
    }

    public function total(): float
    {
        $cart = $this->getCart();

        return (float) $cart['total'];
    }

    public function has(int $productId, ?int $variantId = null): bool
    {
        return $this->contains($productId, $variantId);
    }

    public function contains(int $productId, ?int $variantId = null): bool
    {
        return array_key_exists($this->key($productId, $variantId), $this->rawItems());
    }

    public function quantityOf(int $productId, ?int $variantId = null): int
    {
        $raw = $this->rawItems();

        return (int) ($raw[$this->key($productId, $variantId)]['quantity'] ?? 0);
    }

    /**
     * Merge a guest session cart into the authenticated user's cart, respecting
     * stock. Existing user quantities are kept; guest quantities are added up
     * to the available stock. Returns the merged normalized cart.
     *
     * @return array<string, mixed>
     */
    public function merge(?array $guestItems = null): array
    {
        if (! $this->isAuthenticated()) {
            return $this->getCart();
        }

        $guest = $guestItems ?? $this->sessionItems();
        $cart = $this->cartModel();

        foreach ($guest as $item) {
            $productId = (int) $item['product_id'];
            $variantId = isset($item['variant_id']) && filled($item['variant_id']) ? (int) $item['variant_id'] : null;
            $quantity = max(1, (int) $item['quantity']);

            $product = Product::withTrashed()->find($productId);

            if (! $product || ! $product->status->isVisiblePublicly()) {
                continue;
            }

            $variant = null;
            if ($product->isVariable()) {
                $variant = ProductVariant::withTrashed()->find($variantId);
                if (! $variant || (int) $variant->product_id !== $productId || $variant->trashed()) {
                    continue;
                }
            } elseif (filled($variantId)) {
                continue;
            }

            $existing = $cart->items()->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->first();

            $available = $this->availableQuantity($product, $variant);
            $final = $quantity + (int) ($existing?->quantity ?? 0);

            if ($available !== null && $final > $available) {
                $final = $available;
            }

            if ($final <= 0) {
                continue;
            }

            $unitPrice = $this->resolvePrice($product, $variant);

            if ($existing) {
                $existing->forceFill([
                    'quantity' => $final,
                    'unit_price' => $unitPrice,
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $final,
                    'unit_price' => $unitPrice,
                ]);
            }
        }

        $this->clearSessionCart();

        return $this->getCart();
    }

    /**
     * Re-validate every line against the catalog and stock, drop or clamp
     * invalid lines and return the up-to-date raw items.
     *
     * @return array<string, array<string, mixed>>
     */
    public function refresh(): array
    {
        $raw = $this->rawItems();
        $products = $this->resolveProducts($raw);
        $kept = [];
        $cart = null;

        if ($this->isAuthenticated()) {
            $cart = $this->cartModel();
        }

        foreach ($raw as $key => $item) {
            $productId = (int) $item['product_id'];
            $variantId = filled($item['variant_id'] ?? null) ? (int) $item['variant_id'] : null;

            $product = $products[$productId] ?? null;

            if (! $product || ! $product->status->isVisiblePublicly() || $product->trashed()) {
                $this->dropFromStore($cart, $key, $productId, $variantId);

                continue;
            }

            $variant = null;
            if ($product->isVariable()) {
                $variant = $product->variants->firstWhere('id', $variantId);
                if (! $variant || $variant->trashed()) {
                    $this->dropFromStore($cart, $key, $productId, $variantId);

                    continue;
                }
            } elseif (filled($variantId)) {
                $this->dropFromStore($cart, $key, $productId, $variantId);

                continue;
            }

            $available = $this->availableQuantity($product, $variant);
            $quantity = (int) $item['quantity'];

            if ($available !== null && $quantity > $available) {
                $quantity = $available;
            }

            if ($quantity <= 0) {
                $this->dropFromStore($cart, $key, $productId, $variantId);

                continue;
            }

            $currentUnitPrice = $this->resolvePrice($product, $variant);
            $storedUnitPrice = isset($item['unit_price']) && filled($item['unit_price'])
                ? (float) $item['unit_price']
                : $currentUnitPrice;

            $kept[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $storedUnitPrice,
            ];

            $this->syncDbLine($cart, $productId, $variantId, $quantity, $storedUnitPrice);
        }

        $this->persistRawItems($cart, $kept);

        return $kept;
    }

    /**
     * Maximum purchasable quantity for the given product/variant, or null when
     * the stock is not managed (unlimited).
     */
    public function validateStock(int $productId, ?int $variantId = null, int $quantity = 1): bool
    {
        $product = Product::withTrashed()->find($productId);

        if (! $product || ! $product->status->isVisiblePublicly()) {
            return false;
        }

        $variant = null;
        if ($product->isVariable()) {
            $variant = ProductVariant::withTrashed()->find($variantId);
            if (! $variant || (int) $variant->product_id !== $productId || $variant->trashed()) {
                return false;
            }
        } elseif (filled($variantId)) {
            return false;
        }

        $available = $this->availableQuantity($product, $variant);

        return $available === null || $quantity <= $available;
    }

    /**
     * Build the normalized cart structure used by views and JSON responses.
     *
     * @param  array<string, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function buildCart(array $items): array
    {
        $products = $this->resolveProducts($items);
        $messages = [];
        $lines = [];

        foreach ($items as $key => $item) {
            $productId = (int) $item['product_id'];
            $variantId = filled($item['variant_id'] ?? null) ? (int) $item['variant_id'] : null;

            $product = $products[$productId] ?? null;

            if (! $product || ! $product->status->isVisiblePublicly() || $product->trashed()) {
                $messages[] = ['type' => 'error', 'text' => __('cart.errors.product_removed')];

                continue;
            }

            $variant = null;
            $variantDeleted = false;

            if ($product->isVariable()) {
                $variant = $product->variants->firstWhere('id', $variantId);
                if (! $variant || $variant->trashed()) {
                    $variantDeleted = true;
                    $messages[] = ['type' => 'error', 'text' => __('cart.errors.variant_removed')];
                }
            }

            $requested = (int) $item['quantity'];
            $available = $variantDeleted ? 0 : $this->availableQuantity($product, $variant);
            $quantity = $requested;

            if (! $variantDeleted && $available !== null && $quantity > $available && $available > 0) {
                $quantity = $available;
                $messages[] = ['type' => 'warning', 'text' => __('cart.messages.quantity_adjusted', [
                    'requested' => $requested,
                    'available' => $available,
                ])];
            }

            $price = $this->resolvePrice($product, $variant);
            $oldPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : $price;
            $priceChanged = abs((float) $price - (float) $oldPrice) > 0.0001;

            if ($priceChanged && ! $variantDeleted) {
                $messages[] = ['type' => 'info', 'text' => __('cart.messages.price_updated')];
            }

            $pricing = $this->pricing();

            $grossUnit = $pricing->grossPrice($price);
            $lineTotalCents = $this->toCents($grossUnit) * $quantity;
            $lineTaxCents = $this->toCents($pricing->taxAmountFromGross($grossUnit)) * $quantity;

            $lines[] = [
                'key' => $key,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'requested_quantity' => $quantity !== $requested ? $requested : null,
                'base_price' => (float) $price,
                'unit_price' => (float) $grossUnit,
                'line_total' => $this->fromCents($lineTotalCents),
                'line_tax' => $this->fromCents($lineTaxCents),
                'product' => $product,
                'variant' => $variant,
                'image' => $this->itemImage($product, $variant),
                'name' => $product->translatedName(),
                'url' => localized_route('shop.product.show', ['slug' => $product->translatedSlug()]),
                'brand' => $product->brand?->translatedName() ?? '',
                'sku' => $variant?->sku ?: $product->sku,
                'variant_label' => $variant && $product->isVariable() ? $this->variantLabel($variant) : null,
                'available' => ! $variantDeleted && ($available === null || $available > 0),
                'price_changed' => $priceChanged,
                'old_price' => $priceChanged ? $oldPrice : null,
                'quantity_adjusted' => $quantity !== $requested,
                'stock_limit' => $available,
            ];
        }

        $count = array_sum(array_column($lines, 'quantity'));
        $subtotalCents = array_sum(array_map(fn (array $line): int => $this->toCents($line['line_total']), $lines));
        $taxCents = array_sum(array_map(fn (array $line): int => $this->toCents($line['line_tax']), $lines));
        $subtotal = $this->fromCents($subtotalCents);
        $currency = (string) setting('shop.currency', 'TND');

        $pricing = $this->pricing();
        $shipping = (float) $pricing->shippingCost($subtotal);
        $shippingCents = $this->toCents($shipping);
        $tax = $this->fromCents($taxCents);

        $discount = app(DiscountService::class)->calculateForCart(
            $this->discountContext($lines, $subtotal, $tax, $shipping),
            $this->isAuthenticated() ? Auth::id() : null
        );

        $discountCents = $this->toCents($discount->total);
        $appliedShipping = $discount->freeShipping ? 0.0 : $shipping;
        $appliedShippingCents = $this->toCents($appliedShipping);
        $totalCents = $subtotalCents - $discountCents + $appliedShippingCents;
        $total = $this->fromCents(max(0, $totalCents));

        return [
            'items' => $lines,
            'line_count' => count($lines),
            'count' => $count,
            'empty' => empty($lines),
            'subtotal' => (float) $subtotal,
            'total' => (float) $total,
            'currency' => $currency,
            'currency_symbol' => (string) setting('shop.currency_symbol', 'DT'),
            'coupon_code' => $this->getCouponCode(),
            'applied_coupon' => $this->appliedCouponLine($discount),
            'discounts' => $discount->items,
            'discount_total' => (float) $discount->total,
            'discount_errors' => $discount->errors,
            'free_shipping' => $discount->freeShipping,
            'totals' => [
                'subtotal' => (float) $subtotal,
                'discount' => (float) $discount->total,
                'shipping' => (float) $appliedShipping,
                'tax' => (float) $tax,
                'total' => (float) $total,
                'currency' => $currency,
            ],
            'messages' => array_values($messages),
        ];
    }

    /**
     * Build the contextual payload handed to the discount engine from the
     * normalized cart lines.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function discountContext(array $lines, float $subtotal, float $tax, float $shipping): array
    {
        return [
            'items' => $lines,
            'subtotal' => $subtotal,
            'count' => array_sum(array_column($lines, 'quantity')),
            'coupon_code' => $this->getCouponCode(),
            'totals' => [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
            ],
        ];
    }

    /**
     * @param  \App\Support\DiscountResult  $discount
     * @return array<string, mixed>|null
     */
    private function appliedCouponLine($discount): ?array
    {
        foreach ($discount->items as $line) {
            if (($line['kind'] ?? null) === 'coupon') {
                return $line;
            }
        }

        return null;
    }

    private function pricing(): PricingService
    {
        return app(PricingService::class);
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @return array<int, Product>
     */
    private function resolveProducts(array $items): array
    {
        $ids = array_unique(array_map(fn (array $item): int => (int) $item['product_id'], $items));

        if (empty($ids)) {
            return [];
        }

        return Product::withTrashed()
            ->with([
                'translations',
                'brand.translations',
                'images',
                'variants' => fn ($q) => $q->withTrashed()
                    ->with(['variantValues.attributeValue.translations', 'variantValues.attribute']),
            ])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rawItems(): array
    {
        return $this->isAuthenticated()
            ? $this->cartModel()->items()->get()->mapWithKeys(function ($line) {
                $key = $this->key((int) $line->product_id, $line->product_variant_id);

                return [$key => [
                    'product_id' => (int) $line->product_id,
                    'variant_id' => $line->product_variant_id !== null ? (int) $line->product_variant_id : null,
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ]];
            })->all()
            : $this->sessionItems();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sessionItems(): array
    {
        $cart = Session::get(self::SESSION_KEY);

        return is_array($cart) && isset($cart['items']) && is_array($cart['items']) ? $cart['items'] : [];
    }

    /**
     * Apply a mutation to the items storage (guest session or user DB cart).
     *
     * @param  callable(array<string, array<string, mixed>>): array<string, array<string, mixed>>  $callback
     */
    private function store(int $productId, ?int $variantId, callable $callback): void
    {
        if ($this->isAuthenticated()) {
            $cart = $this->cartModel();
            $unitPrice = null;
            $items = $this->rawItems();
            $next = $callback($items);
            $line = $next[$this->key($productId, $variantId)] ?? null;
            $unitPrice = $line['unit_price'] ?? 0;

            $existing = $cart->items()->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($line === null) {
                if ($existing) {
                    $existing->delete();
                }

                return;
            }

            if ($existing) {
                $existing->forceFill([
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => $unitPrice,
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }

            return;
        }

        $items = $this->sessionItems();
        $next = $callback($items);
        Session::put(self::SESSION_KEY, ['items' => $next, 'coupon_code' => $this->getCouponCode()]);
    }

    private function dropFromStore(?Cart $cart, string $key, int $productId, ?int $variantId): void
    {
        if ($cart) {
            $cart->items()->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->delete();

            return;
        }

        $items = $this->sessionItems();
        unset($items[$key]);
        Session::put(self::SESSION_KEY, ['items' => $items, 'coupon_code' => $this->getCouponCode()]);
    }

    private function syncDbLine(?Cart $cart, int $productId, ?int $variantId, int $quantity, float $unitPrice): void
    {
        if (! $cart) {
            return;
        }

        $cart->items()->updateOrCreate(
            ['product_id' => $productId, 'product_variant_id' => $variantId],
            ['quantity' => $quantity, 'unit_price' => $unitPrice]
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    private function persistRawItems(?Cart $cart, array $items): void
    {
        if ($cart) {
            return;
        }

        Session::put(self::SESSION_KEY, ['items' => $items, 'coupon_code' => $this->getCouponCode()]);
    }

    private function clearSessionCart(): void
    {
        Session::put(self::SESSION_KEY, []);
    }

    private function cartModel(): Cart
    {
        $user = Auth::user();

        return Cart::firstOrCreate(['user_id' => $user->getAuthIdentifier()], [
            'currency' => (string) setting('shop.currency', 'TND'),
        ]);
    }

    private function isAuthenticated(): bool
    {
        return Auth::check();
    }

    private function key(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?: '');
    }

    public function resolvePrice(Product $product, ?ProductVariant $variant): float
    {
        if ($product->isVariable()) {
            $base = (float) ($variant?->price ?? $product->displayPrice() ?? 0);
        } else {
            $base = (float) ($product->price ?? 0);
        }

        $promo = app(PromotionService::class)->promoPriceFor($product, $variant);

        return $promo !== null ? (float) $promo : $base;
    }

    /**
     * Original (non-promoted) base unit price for the given product/variant.
     */
    public function basePrice(Product $product, ?ProductVariant $variant): float
    {
        if ($product->isVariable()) {
            return (float) ($variant?->price ?? $product->displayPrice() ?? 0);
        }

        return (float) ($product->price ?? 0);
    }

    /**
     * Max purchasable quantity, or null when stock is not managed.
     */
    public function availableQuantity(Product $product, ?ProductVariant $variant): ?int
    {
        if ($product->isVariable()) {
            if (! $variant) {
                return 0;
            }
            if (! $variant->manage_stock) {
                return null;
            }
            if ($variant->stock_status === StockStatus::OnBackorder->value) {
                return null;
            }

            return max(0, (int) $variant->stock_quantity);
        }

        if (! $product->manage_stock) {
            return null;
        }
        if ($product->stock_status === StockStatus::OnBackorder->value) {
            return null;
        }

        return max(0, (int) $product->stock_quantity);
    }

    private function itemImage(Product $product, ?ProductVariant $variant): ?string
    {
        if ($variant?->image) {
            return storefront_image($variant->image);
        }

        return $product->primaryImageUrl;
    }

    private function variantLabel(ProductVariant $variant): string
    {
        return $variant->variantValues
            ->sortBy(fn ($value): int => (int) ($value->attribute?->sort_order ?? 0))
            ->map(fn ($value) => $value->attributeValue?->translatedLabel() ?? $value->attributeValue?->value ?? '')
            ->filter()
            ->implode(' / ');
    }

    private function currentQuantity(int $productId, ?int $variantId): int
    {
        return $this->quantityOf($productId, $variantId);
    }

    private function toCents(float|int|string $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
