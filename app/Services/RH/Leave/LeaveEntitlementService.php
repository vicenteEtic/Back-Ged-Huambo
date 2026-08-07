<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveType;
use Carbon\Carbon;

class LeaveEntitlementService
{
    /** Base legal: 22 dias úteis (art. 79.º n.º 1 da Lei 26/22). */
    public const BASE_DAYS = 22;

    /** Acréscimo de 3 dias úteis por cada 10 anos de serviço (art. 79.º n.º 2). */
    public const EXTRA_DAYS_PER_DECADE = 3;

    /** Ano de admissão: 2 dias por mês completo (art. 79.º n.º 3). */
    public const ADMISSION_MONTHLY_DAYS = 2;

    /** Ano de admissão: limite mínimo de 6 dias (art. 79.º n.º 3). */
    public const ADMISSION_MIN_DAYS = 6;

    /**
     * Calcula os dias de férias a que o funcionário tem direito,
     * de acordo com a Lei de Bases da Função Pública (Lei 26/22 de 22 de Agosto):
     *
     *  - Ano de admissão (menos de 1 ano) → 2 dias por mês completo,
     *    com limite mínimo de 6 dias (art. 79.º n.º 3);
     *  - 1 a 9 anos  → 22 dias úteis;
     *  - 10 a 19 anos → 25 dias úteis (+3);
     *  - 20 a 29 anos → 28 dias úteis (+6);
     *  - 30 ou mais anos → 31 dias úteis (+9).
     */
    public function entitledDays(Employee $employee, LeaveType $leaveType): float
    {
        if (! $leaveType->service_years_based) {
            return (float) $leaveType->default_days;
        }

        $years = $this->yearsOfService($employee);

        if ($years < 1) {
            return $this->admissionYearDays($employee);
        }

        return self::BASE_DAYS + self::EXTRA_DAYS_PER_DECADE * floor($years / 10);
    }

    /**
     * Calcula os dias de férias anuais a que o funcionário tem direito,
     * com base no tempo de serviço (Lei 26/22).
     */
    public function annualEntitlement(Employee $employee): array
    {
        $leaveType = LeaveType::where('service_years_based', true)
            ->orderByRaw("CASE WHEN code = 'ANNUAL' THEN 0 ELSE 1 END")
            ->first();

        if (! $leaveType) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'is_annual' => false,
                'message' => 'Não existe tipo de licença anual (service_years_based) configurado.',
            ];
        }

        $start = $this->serviceStartDate($employee);
        $years = $this->yearsOfService($employee);
        $days = $this->entitledDays($employee, $leaveType);

        $result = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'is_annual' => true,
            'leave_type_id' => $leaveType->id,
            'leave_type_code' => $leaveType->code,
            'leave_type_name' => $leaveType->name,
            'service_start_date' => $start?->format('Y-m-d'),
            'years_of_service' => round($years, 2),
            'bracket' => $this->bracket($years),
            'entitled_days' => $days,
        ];

        if ($years < 1) {
            $result['proportional_days'] = $this->admissionYearDays($employee);
            $result['calculation_note'] = 'Ano de admissão (art. 79.º n.º 3 da Lei 26/22): 2 dias por cada mês completo de trabalho, com limite mínimo de 6 dias. O gozo só é permitido após 6 meses de trabalho efectivo (art. 77.º n.º 3).';
        } else {
            $result['calculation_note'] = "Tempo de serviço de {$this->formatYears($years)} → ".$this->bracket($years).": {$days} dias úteis (Lei 26/22).";
        }

        return $result;
    }

    public function bracket(float $years): string
    {
        if ($years < 1) {
            return 'Ano de admissão (2 dias por mês completo, mínimo 6 dias)';
        }

        $decades = (int) floor($years / 10);

        return match ($decades) {
            0 => '1 a 9 anos de serviço',
            1 => '10 a 19 anos de serviço',
            2 => '20 a 29 anos de serviço',
            default => '30 ou mais anos de serviço',
        };
    }

    /**
     * Dias de férias no ano de admissão (art. 79.º n.º 3 da Lei 26/22):
     * 2 dias por mês completo de trabalho, com limite mínimo de 6 dias.
     */
    public function admissionYearDays(Employee $employee): float
    {
        $start = $this->serviceStartDate($employee);

        if (! $start) {
            return (float) self::BASE_DAYS;
        }

        $months = $start->diffInMonths(Carbon::today());

        return (float) max(self::ADMISSION_MIN_DAYS, self::ADMISSION_MONTHLY_DAYS * $months);
    }

    /**
     * Anos de serviço do funcionário (tempo de efectividade/carreira).
     */
    public function yearsOfService(Employee $employee): float
    {
        $start = $this->serviceStartDate($employee);

        if (! $start) {
            return 0.0;
        }

        return $start->diffInYears(Carbon::today());
    }

    /**
     * Verifica se o funcionário completou os 6 meses de trabalho efectivo
     * exigidos para o gozo das férias do ano de admissão (art. 77.º n.º 3 da Lei 26/22).
     */
    public function hasCompletedMinimumService(Employee $employee): bool
    {
        $start = $this->serviceStartDate($employee);

        if (! $start) {
            return true;
        }

        return $start->diffInMonths(Carbon::today()) >= 6;
    }

    public function serviceStartDate(Employee $employee): ?Carbon
    {
        $date = $employee->effective_date ?? $employee->hire_date ?? $employee->institution_entry_date;

        return $date ? Carbon::parse($date) : null;
    }

    private function formatYears(float $years): string
    {
        if ($years < 1) {
            $months = max(1, (int) round($years * 12));

            return "{$months} mês(es)";
        }

        $whole = (int) floor($years);
        $months = (int) round(($years - $whole) * 12);
        $parts = ["{$whole} ano(s)"];

        if ($months > 0) {
            $parts[] = "{$months} mês(es)";
        }

        return implode(' e ', $parts);
    }
}
