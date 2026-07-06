<?php

namespace Tests\Feature\RH\Career;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Career\ProgressionRule;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class ProgressionRuleCheckEligibilityTest extends RhTestCase
{
    public function test_can_check_eligibility()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);
        $rule = ProgressionRule::factory()->create();

        $response = $this->getJsonAuth('/api/rh/progression/rules/' . $rule->id . '/check-eligibility/' . $employee->id);
        $response->assertStatus(200);
    }
}
