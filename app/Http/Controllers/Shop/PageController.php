<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\PageService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function show(Request $request): View
    {
        $slug = (string) $request->route('slug');

        $page = $this->pages->findPublishedBySlug($slug);

        abort_unless($page, 404);

        $translation = $page->translation();

        $seo = app(SeoService::class);

        return view('shop.page', [
            'page' => $page,
            'translation' => $translation,
            'breadcrumbs' => $seo->breadcrumbSchema([
                ['name' => __('messages.nav_home'), 'url' => localized_route('home')],
                ['name' => $page->translatedTitle()],
            ]),
        ]);
    }
}
