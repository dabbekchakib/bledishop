<?php

namespace App\Services;

use App\Models\UrlRedirect;
use Illuminate\Support\Facades\Cache;

class RedirectService
{
    public const CACHE_KEY = 'bledishop.url_redirects.map';

    /**
     * A lookup map of the active redirects: normalised source => redirect id.
     * Cached so the global middleware performs a single in-memory read.
     *
     * @return array<string, int>
     */
    public function activeMap(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): array {
            return UrlRedirect::query()
                ->active()
                ->get(['id', 'source', 'is_active'])
                ->mapWithKeys(static fn (UrlRedirect $redirect): array => [
                    UrlRedirect::normalizeSource($redirect->source) => (int) $redirect->id,
                ])
                ->all();
        });
    }

    /**
     * Resolve an incoming path to a matching active redirect model, if any.
     */
    public function find(string $path): ?UrlRedirect
    {
        $id = $this->activeMap()[UrlRedirect::normalizeSource($path)] ?? null;

        return $id === null ? null : UrlRedirect::query()->active()->find($id);
    }

    /**
     * Build the destination, expanding an internal destination that starts
     * with "/" into an absolute URL (so relative admin inputs work without
     * forcing the admin to type the full domain).
     */
    public function destinationFor(UrlRedirect $redirect): string
    {
        $destination = trim($redirect->destination);

        if ($destination === '') {
            return url('/');
        }

        return str_starts_with($destination, '/')
            ? url($destination)
            : $destination;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
