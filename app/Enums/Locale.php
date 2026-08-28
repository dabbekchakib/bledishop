<?php

namespace App\Enums;

enum Locale: string
{
    case French = 'fr';
    case Arabic = 'ar';
    case English = 'en';

    /**
     * Native label used across the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::French => 'Français',
            self::Arabic => 'العربية',
            self::English => 'English',
        };
    }

    /**
     * HTML text direction for this locale.
     */
    public function direction(): string
    {
        return $this->isRtl() ? 'rtl' : 'ltr';
    }

    public function isRtl(): bool
    {
        return in_array($this->value, self::rtlLocales(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function rtlLocales(): array
    {
        return ['ar', 'fa', 'he', 'ur'];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string> code => native label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $locale) {
            $options[$locale->value] = $locale->label();
        }

        return $options;
    }

    public static function has(string $code): bool
    {
        return in_array($code, self::values(), true);
    }
}
