<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
            self::Archived => 'Archivé',
        };
    }

    public function isVisiblePublicly(): bool
    {
        return $this === self::Active;
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
