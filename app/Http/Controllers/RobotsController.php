<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Services\SeoService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class RobotsController extends Controller
{
    public function __construct(private readonly SeoService $seo) {}

    public function __invoke()
    {
        $lines = ['User-agent: *'];

        if (! (bool) setting('seo.sitemap_enabled', true)) {
            return Response::make(implode(PHP_EOL, $lines).PHP_EOL)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        if (str_contains(strtolower($this->seo->robots()), 'noindex')) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (Locale::values() as $locale) {
                $lines[] = 'Disallow: /'.trim($this->path('login', $locale), '/');
                $lines[] = 'Disallow: /'.trim($this->path('shop.cart.show', $locale), '/');
                $lines[] = 'Disallow: /'.trim($this->path('shop.checkout', $locale), '/');
                $lines[] = 'Disallow: /'.trim($this->path('account.dashboard', $locale), '/');
                $lines[] = 'Disallow: /'.trim($this->path('register', $locale), '/');
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return Response::make(implode(PHP_EOL, $lines).PHP_EOL)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function path(string $name, string $locale): string
    {
        return Route::has($name) ? route($name, ['locale' => $locale], false) : '#';
    }
}
