<?php

namespace App\View\Components;

use App\Services\LocalizationService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{
    public array $locales = [];

    public string $current = 'fr';

    public array $links = [];

    public function __construct(private readonly LocalizationService $localization) {}

    public function render(): View
    {
        $this->current = $this->localization->currentLocale();

        foreach ($this->localization->availableLocales() as $code) {
            $this->locales[$code] = $this->localization->localeLabel($code) ?? $code;
            $this->links[$code] = route('locale.switch', [
                'locale' => $code,
                'redirect' => $this->redirectFor($code),
            ]);
        }

        return view('components.language-switcher');
    }

    private function redirectFor(string $locale): string
    {
        $path = request()->routeIs('locale.switch')
            ? (string) request()->query('redirect', '/'.$locale)
            : '/'.request()->path();

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }
}
