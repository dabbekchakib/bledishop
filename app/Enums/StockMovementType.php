<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';
    case Increase = 'increase';
    case Decrease = 'decrease';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial',
            self::Increase => 'Entrée',
            self::Decrease => 'Sortie',
            self::Adjustment => 'Ajustement',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
