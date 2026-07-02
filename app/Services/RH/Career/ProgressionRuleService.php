<?php

namespace App\Services\RH\Career;

use App\Models\RH\Career\ProgressionRule;
use App\Models\RH\Employee\Employee;
use App\Repositories\RH\Career\ProgressionRuleRepository;
use App\Services\AbstractService;
use Carbon\Carbon;

class ProgressionRuleService extends AbstractService
{
    public function __construct(ProgressionRuleRepository $repository)
    {
        parent::__construct($repository);
    }

    public function checkEligibility(Employee $employee, ?ProgressionRule $rule = null): array
    {
        $rules = $rule ? collect([$rule]) : ProgressionRule::where('is_active', true)->get();
        $results = [];

        foreach ($rules as $r) {
            $checks = [];
            $eligible = true;

            if ($r->from_category && $employee->category !== $r->from_category) {
                $checks[] = ['rule' => 'category_match', 'passed' => false, 'message' => "Categoria actual não corresponde a {$r->from_category}"];
                $eligible = false;
            }

            if ($r->min_months_in_category > 0) {
                $monthsInCategory = $this->getMonthsInCategory($employee);
                $passed = $monthsInCategory >= $r->min_months_in_category;
                if (!$passed) $eligible = false;
                $checks[] = ['rule' => 'min_months_in_category', 'passed' => $passed, 'required' => $r->min_months_in_category, 'actual' => $monthsInCategory];
            }

            if ($r->min_performance_score) {
                $lastScore = $employee->evaluations()->where('status', 'submitted')->orderByDesc('submitted_at')->value('overall_score');
                $passed = $lastScore && $lastScore >= $r->min_performance_score;
                if (!$passed) $eligible = false;
                $checks[] = ['rule' => 'min_performance_score', 'passed' => $passed, 'required' => $r->min_performance_score, 'actual' => $lastScore];
            }

            if ($r->from_level) {
                $currentLevel = $employee->position?->level;
                $passed = $currentLevel && $currentLevel === $r->from_level;
                if (!$passed) $eligible = false;
                $checks[] = ['rule' => 'from_level', 'passed' => $passed, 'required' => $r->from_level, 'actual' => $currentLevel];
            }

            $results[] = [
                'rule_id' => $r->id,
                'rule_name' => $r->name,
                'eligible' => $eligible,
                'checks' => $checks,
                'to_category' => $r->to_category,
                'salary_increase_percent' => $r->salary_increase_percent,
            ];
        }

        return $results;
    }

    public function calculateNewSalary(Employee $employee, ProgressionRule $rule): float
    {
        return round($employee->base_salary * (1 + $rule->salary_increase_percent / 100), 2);
    }

    private function getMonthsInCategory(Employee $employee): int
    {
        $lastChange = $employee->functionalHistory()
            ->where('type', 'category_change')
            ->orderByDesc('effective_date')
            ->first();

        $start = $lastChange?->effective_date ?? $employee->effective_date ?? $employee->hire_date;
        return $start ? $start->diffInMonths(Carbon::today()) : 0;
    }
}
