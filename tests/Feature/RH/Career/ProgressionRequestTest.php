<?php

namespace Tests\Feature\RH\Career;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Career\ProgressionRequest;
use App\Models\RH\Career\ProgressionRule;
use App\Models\RH\Career\ProgressionApproval;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class ProgressionRequestTest extends RhTestCase
{
    protected Employee $employee;
    protected ProgressionRule $rule;
    protected User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $this->user->id,
        ]);

        $this->rule = ProgressionRule::factory()->create();
        $this->requester = User::factory()->create();
    }

    public function test_can_list()
    {
        ProgressionRequest::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/progression/requests');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = ProgressionRequest::factory()->make([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/progression/requests', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/progression/requests/' . $progression->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ]);

        $data = ProgressionRequest::factory()->make([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/progression/requests/' . $progression->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/progression/requests/' . $progression->id);
        $response->assertStatus(204);
    }

    public function test_can_approve()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
            'status' => 'pending',
        ]);

        $response = $this->postJsonAuth('/api/rh/progression/requests/' . $progression->id . '/approve');
        $response->assertStatus(200);
    }

    public function test_can_reject()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
            'status' => 'pending',
        ]);

        $response = $this->postJsonAuth('/api/rh/progression/requests/' . $progression->id . '/reject', [
            'comment' => 'Não cumpre os requisitos mínimos',
        ]);
        $response->assertStatus(200);
    }

    public function test_can_execute()
    {
        $progression = ProgressionRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'rule_id' => $this->rule->id,
            'requested_by' => $this->requester->id,
            'status' => 'approved',
        ]);

        $response = $this->postJsonAuth('/api/rh/progression/requests/' . $progression->id . '/execute');
        $response->assertStatus(200);
    }

    public function test_can_list_rules()
    {
        ProgressionRule::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/progression/rules');
        $response->assertStatus(200);
    }

    public function test_can_check_eligibility()
    {
        $rule = ProgressionRule::factory()->create();

        $response = $this->getJsonAuth('/api/rh/progression/rules/' . $rule->id . '/check-eligibility/' . $this->employee->id);
        $response->assertStatus(200);
    }
}
