<?php

namespace App\Enum;

enum ProgressionType: string
{
    case Progression = 'progression';
    case Promotion = 'promotion';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
