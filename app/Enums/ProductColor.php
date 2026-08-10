<?php

namespace App\Enums;

enum ProductColor: string
{
    case Noir = '#1a1a1a';
    case Blanc = '#ffffff';
    case Gris = '#9e9e9e';
    case Rouge = '#e53935';
    case Rose = '#ec407a';
    case Orange = '#fb8c00';
    case Jaune = '#fbc02d';
    case Beige = '#d7ccc8';
    case Marron = '#6d4c41';
    case Vert = '#43a047';
    case Turquoise = '#26a69a';
    case Bleu = '#1e88e5';
    case BleuMarine = '#1a237e';
    case Violet = '#8e24aa';
    case Or = '#ffc107';
    case Argent = '#b0bec5';

    public function label(): string
    {
        return match ($this) {
            self::Noir => 'Noir',
            self::Blanc => 'Blanc',
            self::Gris => 'Gris',
            self::Rouge => 'Rouge',
            self::Rose => 'Rose',
            self::Orange => 'Orange',
            self::Jaune => 'Jaune',
            self::Beige => 'Beige',
            self::Marron => 'Marron',
            self::Vert => 'Vert',
            self::Turquoise => 'Turquoise',
            self::Bleu => 'Bleu',
            self::BleuMarine => 'Bleu marine',
            self::Violet => 'Violet',
            self::Or => 'Or',
            self::Argent => 'Argent',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $color) => $color->value, self::cases());
    }

    public static function labelOf(?string $hex): ?string
    {
        if ($hex === null || $hex === '') {
            return null;
        }

        $color = self::tryFrom(strtolower($hex));

        return $color?->label() ?? $hex;
    }
}
