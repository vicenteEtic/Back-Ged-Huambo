<?php

namespace App\Enum;

enum AttendanceRequestStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    private const LABELS = [
        'pending' => 'Pendente',
        'under_review' => 'Em análise',
        'approved' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'cancelled' => 'Cancelada',
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
        return array_map(static fn ($case) => $case->label(), self::cases());
    }
}
