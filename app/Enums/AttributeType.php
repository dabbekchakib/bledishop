<?php

namespace App\Enums;

enum AttributeType: string
{
    case Select = 'select';
    case Color = 'color';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Sélection',
            self::Color => 'Couleur',
            self::Text => 'Texte',
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
