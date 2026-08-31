<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlist,
    ) {}

    /**
     * The wishlist page. Accessible to authenticated customers and guests.
     */
    public function index(Request $request): View
    {
        $items = $this->wishlist->list(
            $request->user(),
            $this->guestSession($request),
        );

        return view('shop.wishlist', [
            'items' => $items,
        ]);
    }

    /**
     * Toggle a product in the wishlist (AJAX heart button).
     */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->wishlist->enabled()) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => __('shop.wishlist.disabled'),
            ]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $product = Product::query()->find($validated['product_id']);

        if ($product === null) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => __('shop.wishlist.product_not_found'),
            ]);
        }

        $result = $this->wishlist->toggle(
            $product,
            $request->user(),
            $this->guestSession($request),
        );

        $result['in_wishlist'] = (bool) $result['in_wishlist'];
        $result['message'] = $result['in_wishlist']
            ? __('shop.wishlist.added')
            : __('shop.wishlist.removed');

        return $this->respond($request, $result);
    }

    /**
     * The guest identifier used to persist the temporary wishlist across the
     * session without forcing account creation.
     *
     * A stable token is stored in the session (rather than using the raw
     * session id) so the wishlist survives session-id regeneration and stays
     * consistent regardless of how the session is persisted.
     */
    protected function guestSession(Request $request): ?string
    {
        if ($request->user() !== null) {
            return null;
        }

        $session = $request->session();
        $key = $session->get('wishlist_guest_key');

        if (! is_string($key) || $key === '') {
            $key = (string) Str::uuid();
            $session->put('wishlist_guest_key', $key);
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(Request $request, array $payload): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->back()->with($payload['success'] ? 'success' : 'error', $payload['message']);
    }
}
