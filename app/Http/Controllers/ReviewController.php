<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviews,
        private readonly CatalogService $catalog,
    ) {}

    /**
     * Submit a product review. Requires an enabled reviews feature and, for
     * non-authenticated visitors, the "allow guests" setting. Rate limited in
     * the route to discourage spam.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $product = $this->catalog->findProductBySlug((string) $request->route('slug'));

        if ($product === null) {
            abort(404);
        }

        if (! $this->reviews->enabled()) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => __('shop.reviews.disabled'),
            ]);
        }

        $user = $request->user();

        if (! $this->reviews->canReview($user)) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => $user === null
                    ? __('shop.reviews.guests_forbidden')
                    : __('shop.reviews.form_require_login'),
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'honey' => ['nullable', 'string', 'max:0'],
        ]);

        if (filled($validated['honey'] ?? null)) {
            // Honeypot: silently accept and drop the bot submission.
            return $this->respond($request, [
                'success' => true,
                'message' => __('shop.reviews.success_pending'),
            ]);
        }

        try {
            $this->reviews->submit(
                $product->id,
                (int) $validated['rating'],
                $validated['title'] ?? null,
                $validated['comment'] ?? null,
                $user,
            );
        } catch (\Throwable $e) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => $e->getMessage() ?: __('shop.reviews.error'),
            ]);
        }

        return $this->respond($request, [
            'success' => true,
            'message' => __('shop.reviews.success_pending'),
        ]);
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
