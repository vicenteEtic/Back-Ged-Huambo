<?php

namespace Tests\Feature\RH\Performance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Models\RH\Performance\PerformanceCycle;
use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\EvaluationScore;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class PerformanceEvaluationTest extends RhTestCase
{
    protected Employee $employee;
    protected PerformanceCycle $cycle;
    protected User $evaluator;

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

        $this->cycle = PerformanceCycle::factory()->create();
        $this->evaluator = User::factory()->create();
    }

    public function test_can_list()
    {
        PerformanceEvaluation::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/performance/evaluations');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = PerformanceEvaluation::factory()->make([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/performance/evaluations', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $evaluation = PerformanceEvaluation::factory()->create([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/performance/evaluations/' . $evaluation->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $evaluation = PerformanceEvaluation::factory()->create([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $data = PerformanceEvaluation::factory()->make([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/performance/evaluations/' . $evaluation->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $evaluation = PerformanceEvaluation::factory()->create([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/performance/evaluations/' . $evaluation->id);
        $response->assertStatus(204);
    }

    public function test_can_calculate_score()
    {
        $evaluation = PerformanceEvaluation::factory()->create([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $criterion = EvaluationCriterion::factory()->create([
            'cycle_id' => $this->cycle->id,
            'weight' => 100,
        ]);

        EvaluationScore::factory()->create([
            'evaluation_id' => $evaluation->id,
            'criterion_id' => $criterion->id,
            'score' => 80,
        ]);

        $response = $this->postJsonAuth('/api/rh/performance/evaluations/' . $evaluation->id . '/calculate');
        $response->assertStatus(200);
    }

    public function test_can_list_criteria()
    {
        EvaluationCriterion::factory()->count(3)->create([
            'cycle_id' => $this->cycle->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/performance/criteria');
        $response->assertStatus(200);
    }

    public function test_can_create_criterion()
    {
        $data = EvaluationCriterion::factory()->make([
            'cycle_id' => $this->cycle->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/performance/criteria', $data);
        $response->assertStatus(201);
    }

    public function test_can_list_scores()
    {
        $response = $this->getJsonAuth('/api/rh/performance/scores');
        $response->assertStatus(200);
    }

    public function test_can_list_cycles()
    {
        $response = $this->getJsonAuth('/api/rh/performance/cycles');
        $response->assertStatus(200);
    }

    public function test_can_get_evaluation_scores()
    {
        $evaluation = PerformanceEvaluation::factory()->create([
            'employee_id' => $this->employee->id,
            'cycle_id' => $this->cycle->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/performance/evaluations/' . $evaluation->id . '/scores');
        $response->assertStatus(200);
    }
}
