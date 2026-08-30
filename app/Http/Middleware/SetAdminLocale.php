<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the administrator's preferred locale to the back-office.
 *
 * Priority: session, then the authenticated user's stored locale, then the
 * configured default. The locale is validated against the enabled locales so
 * the Filament interface never falls back to an unknown language.
 */
class SetAdminLocale
{
    public function __construct(private readonly LocalizationService $localization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('admin_locale');

        if ($locale === null && ($user = $request->user()) !== null) {
            $locale = $user->locale;
        }

        if ($locale === null) {
            $locale = $this->localization->defaultLocale();
        }

        if ($locale === null || ! Locale::has($locale) || ! $this->localization->isAvailable($locale)) {
            $locale = $this->localization->isAvailable('fr') ? 'fr' : $this->localization->defaultLocale();
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
