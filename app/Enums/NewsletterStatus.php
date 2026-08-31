<?php

namespace App\Enums;

enum NewsletterStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('admin.newsletter.status_active'),
            self::Unsubscribed => __('admin.newsletter.status_unsubscribed'),
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
