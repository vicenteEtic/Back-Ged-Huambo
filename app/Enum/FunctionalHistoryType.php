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
        self::Appointment => 'Nomeação',
        self::Promotion => 'Promoção',
        self::Progression => 'Progressão',
        self::Transfer => 'Transferência',
        self::PositionChange => 'Mudança de Cargo',
        self::SalaryChange => 'Alteração Salarial',
        self::CategoryChange => 'Mudança de Categoria',
    ];

    public function label(): string
    {
        return self::LABELS[$this] ?? $this->value;
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
