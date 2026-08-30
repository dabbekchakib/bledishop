<?php

namespace App\Enums;

/**
 * Severity of an admin notification, mapped to the Filament colour scheme.
 */
enum NotificationPriority: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    /**
     * The Filament-supported color token for this priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Success => 'success',
            self::Warning => 'warning',
            self::Danger => 'danger',
        };
    }

    /**
     * The badge color token rendered in the notifications centre.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Info => 'blue',
            self::Success => 'green',
            self::Warning => 'amber',
            self::Danger => 'red',
        };
    }
}
