<?php

namespace App\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variable => 'Variable',
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
