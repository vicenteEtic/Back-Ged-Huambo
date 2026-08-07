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
}
