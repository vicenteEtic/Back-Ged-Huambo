<?php

namespace App\Enum;

enum DocumentSharePermission: string
{
    case View = 'view';
    case Download = 'download';
    case Edit = 'edit';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
