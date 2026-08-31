<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ReviewService $reviews,
    ) {}

    public function show(Request $request): View
    {
        $slug = (string) $request->route('slug');

        $product = $this->catalog->findProductBySlug($slug);

        abort_unless($product, 404);

        return view('shop.show', [
            'product' => $product,
            'gallery' => $this->catalog->productGallery($product),
            'attributes' => $this->catalog->productAttributeData($product),
            'variants' => $this->catalog->productVariantData($product),
            'related' => $this->catalog->relatedProducts($product),
            'reviews' => $this->reviews->approvedFor($product->id),
            'review_stats' => $this->reviews->stats($product->id),
            'can_review' => $this->reviews->canReview($request->user()),
            'has_reviewed' => $request->user() !== null && $this->reviews->hasReviewed($request->user(), $product),
        ]);
    }
}
