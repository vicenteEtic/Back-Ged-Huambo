<?php

namespace App\Support;

use Carbon\Carbon;

class TimeNormalizer
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);

        $parts = explode(':', $raw);
        if (count($parts) > 3) {
            $raw = implode(':', array_slice($parts, 0, 3));
        }

        foreach (explode(':', $raw) as $part) {
            if (!preg_match('/^\d{1,2}$/', $part)) {
                return $raw;
            }
        }

        [$h, $m, $s] = array_pad(array_map('intval', explode(':', $raw)), 3, 0);

        if ($h > 23 || $m > 59 || $s > 59) {
            return $raw;
        }

        return Carbon::createFromTime($h, $m, $s)->format('H:i:s');
    }
}
