<?php

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case OnBackorder = 'on_backorder';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'En stock',
            self::OutOfStock => 'Rupture de stock',
            self::OnBackorder => 'Sur commande',
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
