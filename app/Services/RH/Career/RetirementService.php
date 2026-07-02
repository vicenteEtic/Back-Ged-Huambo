<?php

namespace App\Services\RH\Career;

use App\Models\RH\Career\RetirementEligibility;
use App\Models\RH\Employee\Employee;
use Carbon\Carbon;

class RetirementService
{
    public function checkEligibility(Employee $employee): RetirementEligibility
    {
        $birthDate = $employee->date_of_birth;
        $hireDate = $employee->hire_date;
        $now = Carbon::today();

        $age = $birthDate ? $birthDate->age : 0;
        $contributionYears = $hireDate ? round($hireDate->diffInYears($now), 1) : 0;

        $retirementAge = 60;
        $minContribution = 15;

        $ageEligible = $age >= $retirementAge;
        $contributionEligible = $contributionYears >= $minContribution;

        $expectedDate = null;
        if (!$ageEligible && $birthDate) {
            $expectedDate = $birthDate->copy()->addYears($retirementAge);
        }

        return RetirementEligibility::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'retirement_age' => $retirementAge,
                'contribution_years' => $contributionYears,
                'minimum_contribution_years' => $minContribution,
                'age_eligible' => $ageEligible,
                'contribution_eligible' => $contributionEligible,
                'expected_retirement_date' => $expectedDate,
            ]
        );
    }

    public function processHistory(int $employeeId): array
    {
        return \App\Models\RH\Career\RetirementProcess::where('employee_id', $employeeId)
            ->with(['approver', 'postRetirementHistory'])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }
}
