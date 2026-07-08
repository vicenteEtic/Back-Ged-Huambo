<?php

namespace App\Enum;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case JustifiedAbsence = 'justified_absence';
    case Holiday = 'holiday';
    case DayOff = 'day_off';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
