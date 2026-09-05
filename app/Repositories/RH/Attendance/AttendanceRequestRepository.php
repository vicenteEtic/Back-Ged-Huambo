<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\AttendanceRequest;
use App\Repositories\AbstractRepository;

class AttendanceRequestRepository extends AbstractRepository
{
    protected $model;

    public function __construct(AttendanceRequest $model)
    {
        $this->model = $model;
    }

    public function findApprovedCoveringDate(int $employeeId, string $date, bool $fullDayOnly = false)
    {
        return $this->model->query()
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('benefit_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->when($fullDayOnly, fn ($q) => $q->where('applies_full_day', true))
            ->first();
    }

    public function findOverlappingApproved(int $employeeId, string $startDate, string $endDate, ?int $ignoreId = null)
    {
        return $this->model->query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();
    }
}
