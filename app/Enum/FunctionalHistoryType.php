<?php

namespace App\Enum;

enum FunctionalHistoryType: string
{
    case Appointment = 'appointment';
    case Promotion = 'promotion';
    case Progression = 'progression';
    case Transfer = 'transfer';
    case PositionChange = 'position_change';
    case SalaryChange = 'salary_change';
    case CategoryChange = 'category_change';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
