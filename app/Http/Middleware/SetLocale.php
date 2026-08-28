<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly LocalizationService $localization) {}

    /**
     * Resolve, validate and apply the active locale for the request.
     *
     * The locale can be explicit in the URL (first segment) or resolved from
     * the user preference, session, cookie, browser or default setting.
     * Locales that are not enabled are rejected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $urlLocale = $this->urlLocale($request);

        if ($urlLocale !== null && ! $this->localization->isAvailable($urlLocale)) {
            abort(404);
        }

        $this->localization->setActive(
            $this->localization->resolve($request, $urlLocale),
        );

        return $next($request);
    }

    private function urlLocale(Request $request): ?string
    {
        $segment = $request->segment(1);

        if ($segment === null || ! Locale::has($segment)) {
            return null;
        }

        return $segment;
    }
}
