<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Leave\LeaveApproval;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveApprovalFactory extends Factory
{
    protected $model = LeaveApproval::class;

    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'approver_id' => User::factory(),
            'level' => 1,
            'status' => 'pending',
        ];
    }
}
