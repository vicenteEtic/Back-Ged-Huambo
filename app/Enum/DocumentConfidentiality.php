<?php

namespace App\Enum;

enum DocumentConfidentiality: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
