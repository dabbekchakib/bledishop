<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $sort = $this->sort($request);

        $products = $this->catalog->shopProducts($filters, $sort);
        $attributes = $this->catalog->catalogAttributes();

        return view('shop.index', [
            'products' => $products,
            'activeFilters' => $filters,
            'sort' => $sort,
            'attributes' => $attributes,
            'availableBrands' => $this->catalog->availableBrands(),
            'categories' => $this->catalog->categoriesTree(),
            'sortOptions' => $this->catalog->sortOptions(),
        ]);
    }

    public function search(Request $request): View
    {
        $filters = array_merge($this->filters($request), ['q' => trim((string) $request->query('q', ''))]);
        $sort = $this->sort($request);

        $products = $this->catalog->shopProducts($filters, $sort);

        return view('shop.search', [
            'products' => $products,
            'query' => (string) $request->query('q', ''),
            'activeFilters' => $filters,
            'sort' => $sort,
            'attributes' => $this->catalog->catalogAttributes(),
            'availableBrands' => $this->catalog->availableBrands(),
            'categories' => $this->catalog->categoriesTree(),
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
