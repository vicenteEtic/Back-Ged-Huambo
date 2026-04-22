<?php

namespace App\Helpers;

class SanitizeHelper
{
    public static function clean($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }

        if (is_object($data)) {
            return self::clean((array) $data);
        }

        if (is_string($data)) {
            return strip_tags($data);
        }

        return $data;
    }
}