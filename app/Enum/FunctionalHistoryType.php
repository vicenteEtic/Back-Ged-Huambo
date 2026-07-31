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

    private const LABELS = [
        'appointment' => 'Nomeação',
        'promotion' => 'Promoção',
        'progression' => 'Progressão',
        'transfer' => 'Transferência',
        'position_change' => 'Mudança de Cargo',
        'salary_change' => 'Alteração Salarial',
        'category_change' => 'Mudança de Categoria',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_map(fn($case) => $case->label(), self::cases());
    }
}
