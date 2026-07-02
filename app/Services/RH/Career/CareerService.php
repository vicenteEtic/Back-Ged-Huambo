<?php

namespace App\Services\RH\Career;

use App\Models\RH\Employee\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CareerService
{
    public function calculate(Employee $employee): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'total_service' => $this->totalService($employee),
            'time_in_category' => $this->timeInCategory($employee),
            'time_in_position' => $this->timeInPosition($employee),
            'time_in_institution' => $this->timeInInstitution($employee),
            'category' => $employee->category,
            'career_regime' => $employee->career_regime,
            'current_position' => $employee->position?->name,
            'current_department' => $employee->department?->name,
        ];
    }

    public function calculateForAll(array $filters = []): Collection
    {
        $query = Employee::with(['position', 'department', 'functionalHistory']);
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        return $query->get()->map(fn($e) => $this->calculate($e));
    }

    private function totalService(Employee $employee): array
    {
        $start = $employee->effective_date ?? $employee->hire_date;
        return $this->computeInterval($start);
    }

    private function timeInCategory(Employee $employee): array
    {
        $lastChange = $employee->functionalHistory()
            ->where('type', 'category_change')
            ->orderByDesc('effective_date')
            ->first();

        $start = $lastChange?->effective_date
            ?? $employee->effective_date
            ?? $employee->hire_date;

        return $this->computeInterval($start);
    }

    private function timeInPosition(Employee $employee): array
    {
        $lastChange = $employee->functionalHistory()
            ->whereIn('type', ['position_change', 'promotion', 'progression'])
            ->orderByDesc('effective_date')
            ->first();

        $start = $lastChange?->effective_date
            ?? $employee->effective_date
            ?? $employee->hire_date;

        return $this->computeInterval($start);
    }

    private function timeInInstitution(Employee $employee): array
    {
        $start = $employee->institution_entry_date
            ?? $employee->effective_date
            ?? $employee->hire_date;

        return $this->computeInterval($start);
    }

    private function computeInterval(?Carbon $start): array
    {
        if (!$start) {
            return [
                'date' => null,
                'years' => 0,
                'months' => 0,
                'days' => 0,
                'total_days' => 0,
                'formatted' => 'N/A',
            ];
        }

        $now = Carbon::today();
        $diff = $start->diff($now);

        return [
            'date' => $start->format('Y-m-d'),
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'total_days' => $start->diffInDays($now),
            'formatted' => $this->formatInterval($diff->y, $diff->m, $diff->d),
        ];
    }

    private function formatInterval(int $years, int $months, int $days): string
    {
        $parts = [];
        if ($years > 0) $parts[] = "{$years} ano(s)";
        if ($months > 0) $parts[] = "{$months} mês(es)";
        if ($days > 0) $parts[] = "{$days} dia(s)";
        return implode(', ', $parts) ?: '0 dias';
    }
}
