<?php

namespace App\Http\Middleware;

use App\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleUrlRedirects
{
    /**
     * Paths that must never be intercepted by a stored redirect: framework
     * endpoints, the admin panel and static assets.
     */
    private const EXCLUDED_PREFIXES = [
        'admin',
        'livewire',
        'storage',
        '_debugbar',
        '_ignition',
        'api',
        'telescope',
        'horizon',
        'vendor',
        'build',
        'hot',
        'up',
        'sitemap.xml',
        'robots.txt',
    ];

    public function __construct(private readonly RedirectService $redirects) {}

    /**
     * Apply active 301/302 redirects before the route is resolved.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if ($this->shouldSkip($request, $path)) {
            return $next($request);
        }

        $redirect = $this->redirects->find($path);

        if ($redirect !== null) {
            return redirect($this->redirects->destinationFor($redirect), (int) $redirect->status_code);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request, string $path): bool
    {
        if ($request->isMethod('POST')) {
            return true;
        }

        if (str_ends_with($path, '.php') || str_contains($path, '.php/')) {
            return true;
        }

        $parts = explode('/', $path);

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (($parts[0] ?? '') === $prefix) {
                return true;
            }
        }

        if (preg_match('/\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|map|txt)$/i', $path)) {
            return true;
        }

        return false;
    }
}
