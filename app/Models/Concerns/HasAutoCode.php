<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAutoCode
{
    protected static function bootHasAutoCode(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->code)) {
                $model->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = static::$codePrefix ?? 'COD';
        $last = static::withTrashed()
            ->where('code', 'like', "{$prefix}-%")
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->first();

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last->code, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
