<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

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
        ]);
    }
}
