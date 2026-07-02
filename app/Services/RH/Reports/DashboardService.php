<?php

namespace App\Services\RH\Reports;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\EmployeeDocument\EmployeeDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function overview(): array
    {
        $total = Employee::count();
        $active = Employee::where('status', 'active')->count();
        $inactive = $total > 0 ? $total - $active : 0;

        $byDepartment = Employee::select('department_id', DB::raw('count(*) as total'))
            ->where('status', 'active')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get()
            ->map(fn($e) => ['department' => $e->department?->name ?? 'Sem Departamento', 'total' => $e->total]);

        $byGender = Employee::select('gender', DB::raw('count(*) as total'))
            ->where('status', 'active')
            ->groupBy('gender')
            ->get()
            ->pluck('total', 'gender');

        $byContract = Employee::select('contract_type', DB::raw('count(*) as total'))
            ->where('status', 'active')
            ->groupBy('contract_type')
            ->get()
            ->pluck('total', 'contract_type');

        $salary = Employee::where('status', 'active')
            ->select(DB::raw('AVG(base_salary) as average, MIN(base_salary) as minimum, MAX(base_salary) as maximum'))
            ->first();

        return [
            'total_employees' => $total,
            'active_employees' => $active,
            'inactive_employees' => $inactive,
            'by_department' => $byDepartment,
            'by_gender' => $byGender,
            'by_contract_type' => $byContract,
            'salary' => [
                'average' => round((float) ($salary?->average ?? 0), 2),
                'minimum' => round((float) ($salary?->minimum ?? 0), 2),
                'maximum' => round((float) ($salary?->maximum ?? 0), 2),
            ],
        ];
    }

    public function monthlyBirthdays(): array
    {
        $month = now()->month;
        return Employee::where('status', 'active')
            ->whereMonth('date_of_birth', $month)
            ->with(['department:id,name', 'position:id,name'])
            ->orderByRaw('DAY(date_of_birth)')
            ->get(['id', 'full_name', 'date_of_birth', 'department_id', 'position_id', 'photo_url'])
            ->toArray();
    }

    public function leaveSummary(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $byStatus = LeaveRequest::whereYear('created_at', $year)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        $pending = LeaveRequest::where('status', 'pending')
            ->with(['employee:id,full_name', 'leaveType:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'year' => $year,
            'total' => $byStatus->sum(),
            'by_status' => $byStatus,
            'pending_requests' => $pending,
        ];
    }

    public function attendanceSummary(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $lateCount = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'late')
            ->count();

        $absentCount = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('absence_type')
            ->count();

        $overtimeTotal = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('overtime_minutes');

        $presentCount = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'present')
            ->count();

        return [
            'year' => $year,
            'month' => $month,
            'present' => $presentCount,
            'late' => $lateCount,
            'absences' => $absentCount,
            'overtime_minutes' => $overtimeTotal,
        ];
    }

    public function documentExpiryAlert(int $days = 30): array
    {
        $today = now()->startOfDay();
        $deadline = $today->copy()->addDays($days);

        return EmployeeDocument::whereBetween('expiry_date', [$today, $deadline])
            ->with(['employee:id,full_name'])
            ->orderBy('expiry_date')
            ->get()
            ->toArray();
    }

    public function turnover(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $hired = Employee::whereYear('hire_date', $year)->count();
        $left = Employee::where('status', '!=', 'active')
            ->whereYear('updated_at', $year)
            ->count();
        $totalStart = Employee::whereYear('hire_date', '<', $year)->count();
        $turnoverRate = $totalStart > 0
            ? round(($left / ($totalStart + $hired)) * 100, 2)
            : 0;

        return [
            'year' => $year,
            'hired' => $hired,
            'left' => $left,
            'turnover_rate' => $turnoverRate,
        ];
    }

    public function salaryEvolutionByDepartment(): array
    {
        return Employee::where('status', 'active')
            ->select('department_id', DB::raw('AVG(base_salary) as average, MIN(base_salary) as minimum, MAX(base_salary) as maximum, count(*) as total'))
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get()
            ->map(fn($e) => [
                'department' => $e->department?->name ?? 'Sem Departamento',
                'employees' => $e->total,
                'average_salary' => round((float) $e->average, 2),
                'minimum_salary' => round((float) $e->minimum, 2),
                'maximum_salary' => round((float) $e->maximum, 2),
            ]);
    }
}
