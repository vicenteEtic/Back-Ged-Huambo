<?php

namespace App\Services\Process;

use App\Models\Process\ProcessAssignment;
use App\Models\Process\ProcessAssignmentTechnician;
use App\Repositories\Process\ProcessAssignmentRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\DB;

class ProcessAssignmentService extends AbstractService
{
    public function __construct(ProcessAssignmentRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createAssignment(
        int $processId,
        int $departmentId,
        ?int $areaId,
        string $visibility,
        array $technicianIds,
        string $priority = 'normal',
        ?string $deadline = null,
        ?string $notes = null,
    ): ProcessAssignment {
        return DB::transaction(function () use ($processId, $departmentId, $areaId, $visibility, $technicianIds, $priority, $deadline, $notes) {
            $assignment = $this->repository->store([
                'process_id' => $processId,
                'department_id' => $departmentId,
                'area_id' => $areaId,
                'assigned_by' => auth()->id(),
                'visibility' => $visibility,
                'status' => 'pending',
                'priority' => $priority,
                'deadline' => $deadline,
                'notes' => $notes,
            ]);

            foreach ($technicianIds as $technicianId) {
                ProcessAssignmentTechnician::create([
                    'process_assignment_id' => $assignment->id,
                    'user_id' => $technicianId,
                    'assigned_by' => auth()->id(),
                    'status' => 'pending',
                    'assigned_at' => now(),
                ]);
            }

            return $assignment->fresh(['technicians']);
        });
    }

    public function addTechnician(int $assignmentId, int $userId, int $assignedBy): ProcessAssignmentTechnician
    {
        $existing = ProcessAssignmentTechnician::where('process_assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            throw new \Exception('Técnico já está atribuído a esta atribuição.');
        }

        return ProcessAssignmentTechnician::create([
            'process_assignment_id' => $assignmentId,
            'user_id' => $userId,
            'assigned_by' => $assignedBy,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);
    }

    public function removeTechnician(int $assignmentId, int $userId): void
    {
        ProcessAssignmentTechnician::where('process_assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function startTechnician(int $assignmentId, int $technicianId): ProcessAssignmentTechnician
    {
        $tech = ProcessAssignmentTechnician::where('process_assignment_id', $assignmentId)
            ->where('user_id', $technicianId)
            ->firstOrFail();

        $tech->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $assignment = ProcessAssignment::findOrFail($assignmentId);
        if ($assignment->status === 'pending') {
            $assignment->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
        }

        return $tech->fresh();
    }

    public function submitTechnician(int $assignmentId, int $technicianId, array $data = []): ProcessAssignmentTechnician
    {
        $tech = ProcessAssignmentTechnician::where('process_assignment_id', $assignmentId)
            ->where('user_id', $technicianId)
            ->firstOrFail();

        $tech->update(array_merge([
            'status' => 'submitted',
            'submitted_at' => now(),
        ], $data));

        $assignment = ProcessAssignment::with('technicians')->findOrFail($assignmentId);
        if ($assignment->allTechniciansSubmitted()) {
            $assignment->update([
                'status' => 'pending_validation',
                'completed_at' => now(),
            ]);
        }

        return $tech->fresh();
    }
}
