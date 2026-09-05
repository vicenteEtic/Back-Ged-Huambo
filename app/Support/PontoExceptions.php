<?php

namespace App\Support;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use Illuminate\Support\Str;

/**
 * Regra centralizada de excepção ao registo de ponto no RH.
 *
 * Gabinetes que não assinam o livro de ponto no RH (têm livro próprio)
 * são definidos em config/rh.php (por código e/ou nome de departamento).
 */
class PontoExceptions
{
    public static function isEmployeeExempt(?Employee $employee): bool
    {
        return $employee?->department !== null && self::isDepartmentExempt($employee->department);
    }

    public static function isDepartmentExempt(?Department $department): bool
    {
        if (! $department) {
            return false;
        }

        $codes = array_map('strtoupper', (array) config('rh.ponto.exempt_department_codes', []));

        if (in_array(strtoupper(trim((string) $department->code)), $codes, true)) {
            return true;
        }

        $normalizedName = self::normalize($department->name);

        foreach ((array) config('rh.ponto.exempt_department_names', []) as $name) {
            if ($normalizedName === self::normalize($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * IDs dos departamentos com excepção ao livro de ponto do RH.
     */
    public static function exemptDepartmentIds(): array
    {
        return Department::query()
            ->get(['id', 'code', 'name'])
            ->filter(fn (Department $department) => self::isDepartmentExempt($department))
            ->pluck('id')
            ->all();
    }

    private static function normalize(string $value): string
    {
        return strtolower(trim(Str::ascii($value)));
    }
}
