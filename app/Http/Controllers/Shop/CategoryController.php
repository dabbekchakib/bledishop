<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function show(Request $request): View
    {
        $slug = (string) $request->route('slug');

        $category = $this->catalog->findCategoryBySlug($slug);

        abort_unless($category, 404);

        $filters = $this->filters($request);
        $sort = $this->sort($request);

        $products = $this->catalog->categoryProducts($category, $filters, $sort);
        $attributes = $this->catalog->catalogAttributes();

        return view('shop.category', [
            'category' => $category,
            'products' => $products,
            'activeFilters' => $filters,
            'sort' => $sort,
            'attributes' => $attributes,
            'availableBrands' => $this->catalog->availableBrands(),
            'sortOptions' => $this->catalog->sortOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->query('q'),
            'category' => $request->query('category'),
            'brand' => $request->query('brand'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'availability' => $request->query('availability'),
            'attributes' => $request->query('attributes', []),
        ];
    }

    private function sort(Request $request): string
    {
        $sort = (string) $request->query('sort', 'newest');

        return array_key_exists($sort, $this->catalog->sortOptions()) ? $sort : 'newest';
    }
}
