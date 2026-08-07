<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveType;
use Carbon\Carbon;

class LeaveEntitlementService
{
    public const PROPORTIONAL_BASE_DAYS = 22;

    /**
     * Calcula os dias de férias a que o funcionário tem direito.
     *
     * Para tipos de licença com `service_years_based = true` (férias anuais),
     * o número de dias segue a tabela por anos de carreira:
     *
     *  - Até 1 ano      → proporcional ao tempo trabalhado
     *  - 1 a 5 anos     → 22 dias úteis
     *  - 6 a 10 anos    → 23 dias úteis
     *  - 11 a 15 anos   → 24 dias úteis
     *  - 16 a 20 anos   → 25 dias úteis
     *  - 21 a 25 anos   → 26 dias úteis
     *  - Mais de 25 anos → 30 dias úteis
     */
    public function entitledDays(Employee $employee, LeaveType $leaveType): float
    {
        if (!$leaveType->service_years_based) {
            return (float) $leaveType->default_days;
        }

        $years = $this->yearsOfService($employee);

        if ($years < 1) {
            return $this->proportionalDays($employee);
        }

        if ($years < 6) {
            return 22;
        }

        if ($years < 11) {
            return 23;
        }

        if ($years < 16) {
            return 24;
        }

        if ($years < 21) {
            return 25;
        }

        if ($years < 26) {
            return 26;
        }

        return 30;
    }

    /**
     * Calcula os dias de férias anuais a que o funcionário tem direito,
     * com base no tempo de casa (anos de serviço).
     */
    public function annualEntitlement(Employee $employee): array
    {
        $leaveType = LeaveType::where('service_years_based', true)
            ->orderByRaw("CASE WHEN code = 'ANNUAL' THEN 0 ELSE 1 END")
            ->first();

        if (!$leaveType) {
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
            $result['proportional_days'] = $this->proportionalDays($employee);
            $result['calculation_note'] = 'Funcionário com menos de 1 ano de serviço: dias proporcionais (base de 22 dias por 12 meses).';
        } else {
            $result['calculation_note'] = "Tempo de serviço de {$this->formatYears($years)} → " . $this->bracket($years) . ": {$days} dias úteis.";
        }

        return $result;
    }

    public function bracket(float $years): string
    {
        if ($years < 1) {
            return 'Menos de 1 ano de serviço (proporcional)';
        }

        if ($years < 6) {
            return '1 a 5 anos de serviço';
        }

        if ($years < 11) {
            return '6 a 10 anos de serviço';
        }

        if ($years < 16) {
            return '11 a 15 anos de serviço';
        }

        if ($years < 21) {
            return '16 a 20 anos de serviço';
        }

        if ($years < 26) {
            return '21 a 25 anos de serviço';
        }

        return 'Mais de 25 anos de serviço';
    }

    /**
     * Dias proporcionais ao tempo efectivamente trabalhado (menos de 1 ano).
     * Base: 22 dias por 12 meses completos de serviço.
     */
    public function proportionalDays(Employee $employee): float
    {
        $start = $this->serviceStartDate($employee);

        if (!$start) {
            return (float) self::PROPORTIONAL_BASE_DAYS;
        }

        $months = $start->diffInMonths(Carbon::today());

        return round(self::PROPORTIONAL_BASE_DAYS * min($months, 12) / 12, 1);
    }

    /**
     * Anos de serviço do funcionário (tempo de efectividade/carreira).
     */
    public function yearsOfService(Employee $employee): float
    {
        $start = $this->serviceStartDate($employee);

        if (!$start) {
            return 0.0;
        }

        return $start->diffInYears(Carbon::today());
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
