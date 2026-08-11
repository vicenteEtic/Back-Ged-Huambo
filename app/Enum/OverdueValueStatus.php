<?php

namespace App\Enum;

enum OverdueValueStatus: string
{
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Settled = 'settled';
    case Cancelled = 'cancelled';

    private const LABELS = [
        'pending' => 'Pendente',
        'partially_paid' => 'Parcialmente Liquidado',
        'settled' => 'Liquidado',
        'cancelled' => 'Cancelado',
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
