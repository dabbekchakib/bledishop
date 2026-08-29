<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemap) {}

    public function index()
    {
        abort_unless((bool) setting('seo.sitemap_enabled', true), 404);

        $urls = $this->sitemap->urls();

        return Response::view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
