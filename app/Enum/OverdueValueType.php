<?php

namespace App\Enum;

enum OverdueValueType: string
{
    case Receivable = 'receivable';
    case Payable = 'payable';

    private const LABELS = [
        'receivable' => 'A Receber',
        'payable' => 'A Pagar',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
