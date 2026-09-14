<?php

namespace App\Support;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Services\RH\Attendance\AttendanceBookConfigService;
use Illuminate\Support\Str;

/**
 * Regra centralizada de excepção/eligibilidade ao registo de ponto no RH.
 *
 * Quem assina o livro de ponto é configurável: se existir uma lista positiva
 * persistida em BD (attendance_book_configs → attendance_book_departments),
 * apenas esses departamentos/gabinetes assinam. Sem configuração persiste a
 * regra legada (config/rh.php → ponto.exempt_department_codes/names) — sem
 * lista hardcoded no backend.
 */
class PontoExceptions
{
    public static function isEmployeeExempt(?Employee $employee): bool
    {
        return $employee === null || ! self::isEmployeeEligible($employee);
    }

    public static function isEmployeeEligible(?Employee $employee): bool
    {
        if (! $employee || ! $employee->department) {
            return false;
        }

        $configured = self::configuredBookDepartmentIds();

        if ($configured !== null) {
            return in_array((int) $employee->department_id, $configured, true);
        }

        return ! self::isExemptByCodeOrName($employee->department);
    }

    public static function isDepartmentExempt(?Department $department): bool
    {
        if (! $department) {
            return false;
        }

        $configured = self::configuredBookDepartmentIds();

        if ($configured !== null) {
            return ! in_array((int) $department->id, $configured, true);
        }

        return self::isExemptByCodeOrName($department);
    }

    /**
     * IDs dos departamentos que NÃO assinam o livro de ponto do RH
     * (usado para excluir departamentos das listagens de assiduidade).
     */
    public static function exemptDepartmentIds(): array
    {
        $configured = self::configuredBookDepartmentIds();

        if ($configured !== null) {
            $all = array_map('intval', Department::query()->pluck('id')->all());

            return array_values(array_diff($all, $configured));
        }

        return Department::query()
            ->get(['id', 'code', 'name'])
            ->filter(fn (Department $department) => self::isExemptByCodeOrName($department))
            ->pluck('id')
            ->all();
    }

    /**
     * Lista positiva (gerida em BD) dos departamentos que assinam o livro.
     * Devolve null quando não configurada → usa a regra legada.
     */
    public static function configuredBookDepartmentIds(): ?array
    {
        $ids = app(AttendanceBookConfigService::class)->departmentIds();

        return empty($ids) ? null : $ids;
    }

    private static function isExemptByCodeOrName(Department $department): bool
    {
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

    private static function normalize(string $value): string
    {
        return strtolower(trim(Str::ascii($value)));
    }
}