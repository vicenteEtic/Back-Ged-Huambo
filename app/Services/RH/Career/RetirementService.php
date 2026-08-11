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

    /**
     * Funcionários activos que poderão ir para a aposentação num horizonte de anos
     * (por omissão: idade de reforma menos 5 anos), ordenados pela data esperada.
     */
    public function upcomingRetirees(int $withinYears = 5): array
    {
        $retirementAge = 60;
        $thresholdAge = $retirementAge - $withinYears;

        $employees = Employee::query()
            ->where('status', 'active')
            ->with(['department:id,name', 'position:id,name'])
            ->get();

        $list = [];

        foreach ($employees as $employee) {
            if (blank($employee->date_of_birth)) {
                continue;
            }

            $age = $employee->date_of_birth->age;
            $expectedDate = $employee->date_of_birth->copy()->addYears($retirementAge);

            if ($age < $thresholdAge) {
                continue;
            }

            $eligibleNow = $age >= $retirementAge;

            $list[] = [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'gender' => $employee->gender,
                'department' => $employee->department?->name,
                'position' => $employee->position?->name,
                'age' => $age,
                'retirement_age' => $retirementAge,
                'expected_retirement_date' => $expectedDate->toDateString(),
                'years_until_retirement' => $eligibleNow ? 0 : $retirementAge - $age,
                'eligible_now' => $eligibleNow,
            ];
        }

        usort($list, fn ($a, $b) => $a['expected_retirement_date'] <=> $b['expected_retirement_date']);

        return [
            'within_years' => $withinYears,
            'retirement_age' => $retirementAge,
            'total' => count($list),
            'eligible_now' => count(array_filter($list, fn ($item) => $item['eligible_now'])),
            'employees' => $list,
        ];
    }
}
