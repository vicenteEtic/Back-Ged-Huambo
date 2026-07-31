<?php

namespace Tests\Feature\Process;

use Tests\Feature\RH\RhTestCase;
use App\Models\Process\Process;
use App\Models\Process\ProcessAssignment;
use App\Models\Process\ProcessAssignmentTechnician;
use App\Models\RH\Department\Department;
use App\Models\RH\Area\Area;
use App\Models\User;

class ProcessWorkflowTest extends RhTestCase
{
    public function test_can_dispatch_to_chief(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $targetDept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'received_by' => $user->id,
            'created_by' => $user->id,
            'status' => 'received',
        ]);

        $response = $this->postJsonAuth(route('process.dispatchToChief', $process->id), [
            'department_id' => $targetDept->id,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'dispatched_to_chief']);
    }

    public function test_can_dispatch_to_areas(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $targetDept = Department::factory()->gabinete()->create();
        $area1 = Area::factory()->create(['department_id' => $targetDept->id]);
        $area2 = Area::factory()->create(['department_id' => $targetDept->id]);
        $tech1 = User::factory()->create();
        $tech2 = User::factory()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'received_by' => $user->id,
            'created_by' => $user->id,
            'status' => 'received',
        ]);

        $response = $this->postJsonAuth(route('process.dispatchToAreas', $process->id), [
            'assignments' => [
                [
                    'department_id' => $targetDept->id,
                    'area_id' => $area1->id,
                    'visibility' => 'private',
                    'technicians' => [$tech1->id],
                    'priority' => 'normal',
                ],
                [
                    'department_id' => $targetDept->id,
                    'area_id' => $area2->id,
                    'visibility' => 'public',
                    'technicians' => [$tech2->id],
                    'priority' => 'high',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'dispatched_to_areas']);
    }

    public function test_can_add_technician(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $area = Area::factory()->create(['department_id' => $dept->id]);
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
            'area_id' => $area->id,
        ]);

        $newTech = User::factory()->create();

        $response = $this->postJsonAuth(
            route('process.addTechnician', [$process->id, $assignment->id]),
            ['user_id' => $newTech->id, 'visibility' => 'private']
        );
        $response->assertStatus(200);
    }

    public function test_can_remove_technician(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
        ]);

        $tech = ProcessAssignmentTechnician::factory()->create([
            'process_assignment_id' => $assignment->id,
            'status' => 'pending',
        ]);

        $response = $this->deleteJsonAuth(
            route('process.removeTechnician', [$process->id, $assignment->id, $tech->user_id])
        );
        $response->assertStatus(204);
    }

    public function test_can_make_public(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->private()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
        ]);

        $response = $this->postJsonAuth(
            route('process.makePublic', [$process->id, $assignment->id])
        );
        $response->assertStatus(200);
        $response->assertJsonFragment(['visibility' => 'public']);
    }

    public function test_can_start_by_technician(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
        ]);

        $tech = ProcessAssignmentTechnician::factory()->create([
            'process_assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        $response = $this->postJsonAuth(
            route('process.startByTechnician', [$process->id, $assignment->id, $user->id])
        );
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'processing']);
    }

    public function test_can_submit_by_technician(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->processing()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
        ]);

        $tech = ProcessAssignmentTechnician::factory()->processing()->create([
            'process_assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        $response = $this->postJsonAuth(
            route('process.submitByTechnician', [$process->id, $assignment->id, $user->id]),
            ['notes' => 'Trabalho concluído']
        );
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'submitted']);
    }

    public function test_can_validate_assignment(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $dept = Department::factory()->gabinete()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $assignment = ProcessAssignment::factory()->pendingValidation()->create([
            'process_id' => $process->id,
            'department_id' => $dept->id,
        ]);

        ProcessAssignmentTechnician::factory()->submitted()->create([
            'process_assignment_id' => $assignment->id,
        ]);

        $response = $this->postJsonAuth(
            route('process.validateAssignment', [$process->id, $assignment->id])
        );
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'validated']);
    }

    public function test_can_close_process(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'validated_by_director',
        ]);

        $response = $this->postJsonAuth(route('process.close', $process->id));
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'closed']);
    }

    public function test_can_reject_process(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $response = $this->postJsonAuth(route('process.reject', $process->id), [
            'reason' => 'Documento incompleto',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'rejected']);
    }

    public function test_can_store_comment(): void
    {
        $expediente = Department::factory()->expediente()->create();
        $user = $this->user;

        $process = Process::factory()->create([
            'current_department_id' => $expediente->id,
            'current_holder_id' => $user->id,
            'status' => 'received',
        ]);

        $response = $this->postJsonAuth(route('process.storeComment', $process->id), [
            'comment' => 'Necessário análise detalhada',
            'comment_type' => 'instruction',
        ]);
        $response->assertStatus(201);
    }
}
