<?php

namespace App\Services;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LocalizationService
{
    public const SESSION_KEY = 'locale';

    public const COOKIE_NAME = 'locale';

    public const COOKIE_LIFETIME_MINUTES = 525600;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<int, string>
     */
    public function availableLocales(bool $fresh = false): array
    {
        $locales = $this->settings->get('localization.available_locales', []);

        if (! is_array($locales)) {
            return [];
        }

        return array_values(array_filter(
            array_unique(array_map(fn (mixed $code): string => (string) $code, $locales)),
            static fn (string $code): bool => Locale::has($code),
        ));
    }

    public function defaultLocale(bool $fresh = false): string
    {
        $default = (string) $this->settings->get('localization.default_locale', 'fr');
        $available = $this->availableLocales($fresh);

        if ($default === '' || ! in_array($default, $available, true)) {
            return $available[0] ?? 'fr';
        }

        return $default;
    }

    public function isAvailable(string $locale, bool $fresh = false): bool
    {
        return Locale::has($locale) && in_array($locale, $this->availableLocales($fresh), true);
    }

    public function currentLocale(): string
    {
        $current = app()->getLocale();

        return Locale::has($current) ? $current : $this->defaultLocale();
    }

    public function direction(?string $locale = null): string
    {
        return $this->localeOf($locale)->direction();
    }

    public function isRtl(?string $locale = null): bool
    {
        return $this->localeOf($locale)->isRtl();
    }

    /**
     * @return array{lang: string, dir: string}
     */
    public function htmlAttributes(?string $locale = null): array
    {
        $locale = $this->localeOf($locale);

        return [
            'lang' => $locale->value,
            'dir' => $locale->direction(),
        ];
    }

    public function localeLabel(string $locale): ?string
    {
        return Locale::tryFrom($locale)?->label();
    }

    /**
     * Resolve the active locale following the configured priority:
     * URL > authenticated user > session > cookie > browser > default.
     */
    public function resolve(Request $request, ?string $urlLocale = null): string
    {
        if ($urlLocale !== null && $this->isAvailable($urlLocale)) {
            return $urlLocale;
        }

        if (($user = $request->user()) && filled($user->locale) && $this->isAvailable($user->locale)) {
            return $user->locale;
        }

        $sessionLocale = session(self::SESSION_KEY);

        if (is_string($sessionLocale) && $this->isAvailable($sessionLocale)) {
            return $sessionLocale;
        }

        $cookieLocale = (string) $request->cookie(self::COOKIE_NAME, '');

        if ($cookieLocale !== '' && $this->isAvailable($cookieLocale)) {
            return $cookieLocale;
        }

        if ($this->browserDetectionEnabled()) {
            $browserLocale = $this->detectBrowserLocale($request);

            if ($browserLocale !== null) {
                return $browserLocale;
            }
        }

        return $this->defaultLocale();
    }

    /**
     * Apply a validated locale as the active application locale and persist it.
     */
    public function setActive(string $locale): void
    {
        App::setLocale($locale);
        App::setFallbackLocale($this->defaultLocale());

        session([self::SESSION_KEY => $locale]);

        Cookie::queue(Cookie::forever(self::COOKIE_NAME, $locale));
    }

    /**
     * Best-effort browser language detection (Accept-Language). Never returns
     * a locale that is not enabled.
     */
    public function detectBrowserLocale(Request $request): ?string
    {
        $header = $request->headers->get('Accept-Language', '');

        if ($header === '') {
            return null;
        }

        $available = $this->availableLocales();

        preg_match_all('/([a-z]{2})[-_]/i', $header, $matches);

        $candidates = $matches[1] ?? [];

        $headerLocales = [];
        preg_match_all('/(?:^|,)\s*([a-z]{2})(?:-[A-Z]{2})?(?:;q=[0-9.]+)?/i', $header, $headerLocales);

        foreach (array_merge($headerLocales[1] ?? [], $candidates) as $candidate) {
            $candidate = strtolower($candidate);

            if (in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        return null;
    }

    public function browserDetectionEnabled(): bool
    {
        return (bool) $this->settings->get('localization.browser_detection_enabled', true);
    }

    /**
     * URL for the current page in every enabled locale. Prepared for hreflang
     * and SEO: only pages already expressed in a localized URL are returned.
     *
     * @return array<string, string> locale => localized path
     */
    public function localizedUrlsForCurrentRequest(?string $currentPath = null): array
    {
        $currentPath ??= '/'.request()->path();

        $urls = [];

        foreach ($this->availableLocales() as $code) {
            $path = $this->replaceLocalePrefix($code, $currentPath);

            if (str_starts_with($currentPath, '/'.app()->getLocale())) {
                $urls[$code] = $path;
            }
        }

        return $urls;
    }

    /**
     * The path the user should be redirected to after a language switch,
     * preserving the current page whenever it is a localized URL.
     */
    public function switchTarget(string $locale, Request $request): string
    {
        $path = $request->routeIs('locale.switch')
            ? (string) $request->query('redirect', '')
            : '/'.request()->path();

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/'.$locale;
        }

        return $this->replaceLocalePrefix($locale, $path);
    }

    private function replaceLocalePrefix(string $locale, string $path): string
    {
        $segments = explode('/', trim($path, '/'));

        if (($segments[0] ?? '') !== '' && Locale::has($segments[0])) {
            $segments[0] = $locale;

            return '/'.implode('/', $segments);
        }

        return $path === '/' || $path === '' ? '/'.$locale : $path;
    }

    private function localeOf(?string $locale = null): Locale
    {
        $code = $locale ?? $this->currentLocale();

        return Locale::tryFrom($code) ?? Locale::French;
    }
}
