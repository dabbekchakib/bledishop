<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(): View
    {
        return view('shop.home', [
            'featuredCategories' => $this->catalog->featuredCategories(),
            'featuredBrands' => $this->catalog->featuredBrands(),
            'featuredProducts' => $this->catalog->featuredProducts(8),
            'newProducts' => $this->catalog->newProducts(8),
            'promoProducts' => $this->catalog->promoProducts(8),
        ]);
    }
}
