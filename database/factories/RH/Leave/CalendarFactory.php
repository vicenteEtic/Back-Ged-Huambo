<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarFactory extends Factory
{
    public function definition(): array
    {
        return [];
    }

    public static function createCalendarData(int $year, int $employeeId): array
    {
        $plan = LeavePlan::firstOrCreate(
            ['employee_id' => $employeeId, 'year' => $year],
            ['total_days_entitled' => 22, 'days_used' => 0, 'days_pending' => 0, 'days_remaining' => 22]
        );

        LeaveRequest::factory()->count(3)->create([
            'employee_id' => $employeeId,
            'leave_plan_id' => $plan->id,
            'status' => 'approved',
        ]);

        return ['plan' => $plan->fresh()];
    }
}
