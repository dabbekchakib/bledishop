<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function show(Request $request): View
    {
        $cart = $this->cart->getCart();

        return view('shop.cart', [
            'cart' => $cart,
            'similar' => $this->similar($cart),
        ]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $productId = (int) $request->input('product_id');
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $quantity = max(1, (int) $request->input('quantity', 1));

        try {
            $this->cart->add($productId, $variantId, $quantity);
            $cart = $this->cart->getCart();
        } catch (\Throwable $e) {
            return $this->failure($request, $e);
        }

        return $this->respond($request, [
            'success' => true,
            'message' => __('cart.messages.added'),
            'cart_count' => $cart['count'],
            'item_count' => $cart['line_count'],
            'subtotal' => $cart['subtotal'],
            'cart_subtotal' => format_price($cart['subtotal']),
            'cart_total' => format_price($cart['total']),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        [$productId, $variantId] = $this->resolveItem((string) $request->route('item'));
        $quantity = (int) $request->input('quantity', 1);

        try {
            $cart = $quantity > 0
                ? $this->cart->update($productId, $variantId, $quantity)
                : $this->cart->remove($productId, $variantId);
        } catch (\Throwable $e) {
            return $this->failure($request, $e);
        }

        return $this->respond($request, $this->lineResponse($cart, $productId, $variantId, $quantity));
    }

    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        [$productId, $variantId] = $this->resolveItem((string) $request->route('item'));

        try {
            $this->cart->remove($productId, $variantId);
            $cart = $this->cart->getCart();
        } catch (\Throwable $e) {
            return $this->failure($request, $e);
        }

        return $this->respond($request, [
            'success' => true,
            'message' => __('cart.messages.removed'),
            'cart_count' => $cart['count'],
            'item_count' => $cart['line_count'],
            'subtotal' => $cart['subtotal'],
            'cart_subtotal' => format_price($cart['subtotal']),
            'cart_total' => format_price($cart['total']),
            'empty' => $cart['empty'],
        ]);
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->clear();

        return $this->respond($request, [
            'success' => true,
            'message' => __('cart.messages.cleared'),
            'cart_count' => 0,
            'item_count' => 0,
            'subtotal' => 0,
            'cart_subtotal' => format_price(0),
            'cart_total' => format_price(0),
            'empty' => true,
        ]);
    }

    /**
     * Store + validate the submitted promo code. Invalid codes are removed
     * again so the cart never keeps a stale reference.
     */
    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $code = strtoupper(trim((string) $request->input('code')));
        $this->cart->setCouponCode($code);

        $cart = $this->cart->getCart();
        $errors = $cart['discount_errors'] ?? [];
        $applied = $cart['applied_coupon'] ?? null;

        if ($applied) {
            return $this->respond($request, [
                'success' => true,
                'message' => __('shop.marketing.coupon.applied', ['code' => $code]),
                'coupon_code' => $code,
                'discount_total' => $cart['discount_total'],
                'cart_total' => $cart['total'],
                'cart_subtotal' => format_price($cart['subtotal']),
                'summary_html' => view('components.storefront.cart-summary', ['cart' => $cart])->render(),
            ]);
        }

        $this->cart->removeCoupon();
        $message = $errors ? __((string) head($errors)) : __('shop.marketing.coupon.unusable');

        return $this->respondError($request, $message);
    }

    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->removeCoupon();
        $cart = $this->cart->getCart();

        return $this->respond($request, [
            'success' => true,
            'message' => __('shop.marketing.coupon.removed'),
            'coupon_code' => null,
            'discount_total' => 0,
            'cart_total' => $cart['total'],
            'summary_html' => view('components.storefront.cart-summary', ['cart' => $cart])->render(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respondError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'type' => 'warning',
            ], 422);
        }

        return redirect()->back()->with('warning', $message);
    }

    /**
     * Fresh drawer markup for the mini-cart, used by the Alpine cart store.
     */
    public function drawer(): View
    {
        return view('components.storefront.cart-drawer-body', [
            'cart' => $this->cart->getCart(),
        ]);
    }

    /**
     * Cart page fragments (items + summary) re-rendered after AJAX mutations.
     */
    public function fragments(Request $request): JsonResponse
    {
        $cart = $this->cart->getCart();

        return response()->json([
            'success' => true,
            'items_html' => view('shop.partials.cart-items', ['cart' => $cart])->render(),
            'summary_html' => view('components.storefront.cart-summary', ['cart' => $cart, 'sticky' => true])->render(),
            'cart_count' => $cart['count'],
            'item_count' => $cart['line_count'],
            'subtotal' => $cart['subtotal'],
            'cart_subtotal' => format_price($cart['subtotal']),
            'cart_total' => format_price($cart['total']),
            'empty' => $cart['empty'],
        ]);
    }

    /**
     * Route-level lookup helper so the merchant-facing "place order" CTA stays
     * valid until the checkout module (Prompt 8) replaces this endpoint.
     */
    public function checkoutPlaceholder(Request $request): RedirectResponse
    {
        return redirect()->route('shop.cart.show', ['locale' => $request->route('locale')])
            ->with('info', __('cart.messages.checkout_coming_soon'));
    }

    /**
     * @return array<int, int|null> [product_id, variant_id|null]
     */
    private function resolveItem(string $item): array
    {
        [$product, $variant] = array_pad(explode(':', $item, 2), 2, null);

        $productId = (int) $product;
        $variantId = filled($variant) ? (int) $variant : null;

        return [$productId, $variantId];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineResponse(array $cart, int $productId, ?int $variantId, int $quantity): array
    {
        $line = collect($cart['items'])->first(
            fn (array $item): bool => $item['product_id'] === $productId
                && $item['variant_id'] === $variantId,
        );

        return [
            'success' => true,
            'message' => __('cart.messages.updated'),
            'cart_count' => $cart['count'],
            'item_count' => $cart['line_count'],
            'subtotal' => $cart['subtotal'],
            'cart_subtotal' => format_price($cart['subtotal']),
            'cart_total' => format_price($cart['total']),
            'empty' => $cart['empty'],
            'line_total' => $line['line_total'] ?? 0,
            'line_quantity' => $line['quantity'] ?? $quantity,
            'line_removed' => ! $line,
            'adjusted' => (bool) ($line['quantity_adjusted'] ?? false),
            'line_html' => $line ? view('components.storefront.cart-item', ['item' => $line, 'cart' => $cart])->render() : null,
            'summary_html' => view('components.storefront.cart-summary', ['cart' => $cart])->render(),
        ];
    }

    private function similar(array $cart): Collection
    {
        $inCart = collect($cart['items'])->pluck('product_id')->all();

        $categoryIds = collect($cart['items'])
            ->flatMap(fn (array $item) => $item['product']->categories()->pluck('categories.id'))
            ->unique()
            ->values()
            ->all();

        $query = Product::query()
            ->public()
            ->with(['translations', 'brand.translations', 'images'])
            ->whereNotIn('id', $inCart ?: [0]);

        if (! empty($categoryIds)) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        } else {
            $query->featured();
        }

        return $query->inRandomOrder()->limit(4)->get();
    }

    private function failure(Request $request, \Throwable $e): JsonResponse|RedirectResponse
    {
        $type = $e instanceof ValidationException ? 'warning' : 'error';
        $message = $e instanceof ValidationException
            ? collect($e->errors())->flatten()->first()
            : ($e->getMessage() ?: __('cart.errors.generic'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'type' => $type,
            ], $e instanceof ValidationException ? 422 : 422);
        }

        return redirect()->back()->with($type, $message);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(Request $request, array $payload): JsonResponse|RedirectResponse
    {
        $payload['type'] = 'success';

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->back()->with('success', $payload['message']);
    }
}
