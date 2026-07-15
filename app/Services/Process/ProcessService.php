<?php

namespace App\Services\Process;

use App\Models\Process\Process;
use App\Models\Process\ProcessAssignment;
use App\Models\Process\ProcessAssignmentTechnician;
use App\Models\Process\ProcessDocument;
use App\Models\Process\ProcessMovement;
use App\Repositories\Process\ProcessRepository;
use App\Services\AbstractService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProcessService extends AbstractService
{
    public function __construct(
        ProcessRepository $repository,
        protected ProcessAssignmentService $assignmentService,
    ) {
        parent::__construct($repository);
    }

    public function inbox(int $userId, int $departmentId)
    {
        return $this->repository->inbox($userId, $departmentId);
    }

    public function outbox(int $userId)
    {
        return $this->repository->outbox($userId);
    }

    public function history()
    {
        return $this->repository->history();
    }

    public function store(array $data): Process
    {
        $files = $data['file_path'] ?? null;
        $documentType = $data['document_type'] ?? null;
        unset($data['file_path'], $data['document_type']);

        $data = $this->clean($data);

        $expediente = \App\Models\RH\Department\Department::where('type', 'expediente')->firstOrFail();

        $data['current_department_id'] = $expediente->id;
        $data['current_holder_id'] = auth()->id();
        $data['sequence_number'] = $this->repository->nextSequenceNumber($expediente->code);
        $data['status'] = 'received';
        $data['received_by'] = auth()->id();
        $data['created_by'] = auth()->id();

        $model = $this->repository->store($data);

        if ($files) {
            $this->storeDocuments($model->id, $files, $documentType);
        }

        $this->registerMovement($model, null, null, $expediente->id, null, 'reception', 'Processo criado no expediente');

        return $model->fresh(['currentDepartment', 'currentArea', 'currentHolder', 'creator', 'documents']);
    }

    public function dispatchToChief(int $processId, int $departmentId, ?int $areaId = null): Process
    {
        return DB::transaction(function () use ($processId, $departmentId, $areaId) {
            $process = $this->repository->show($processId);

            if ($process->status !== 'received') {
                throw new \Exception('Processo não está em estado de recepção.');
            }

            $chiefUserId = $this->findDepartmentChief($departmentId);

            $process->update([
                'status' => 'dispatched_to_chief',
                'current_department_id' => $departmentId,
                'current_area_id' => $areaId,
                'current_holder_id' => $chiefUserId,
            ]);

            $this->registerMovement(
                $process,
                $process->getOriginal('current_department_id'),
                $process->getOriginal('current_area_id'),
                $departmentId,
                $areaId,
                'dispatch_to_chief',
                'Encaminhado ao chefe do departamento',
                null,
                $chiefUserId
            );

            return $process->fresh(['currentDepartment', 'currentArea', 'currentHolder']);
        });
    }

    public function dispatchToAreas(int $processId, array $assignmentsData): Process
    {
        return DB::transaction(function () use ($processId, $assignmentsData) {
            $process = $this->repository->show($processId);

            if (!in_array($process->status, ['received', 'dispatched_to_chief', 'correction_requested'])) {
                throw new \Exception('Processo não pode ser distribuído a áreas neste estado.');
            }

            foreach ($assignmentsData as $areaData) {
                $this->assignmentService->createAssignment(
                    $processId,
                    $areaData['department_id'],
                    $areaData['area_id'] ?? null,
                    $areaData['visibility'] ?? 'private',
                    $areaData['technicians'] ?? [],
                    $areaData['priority'] ?? 'normal',
                    $areaData['deadline'] ?? null,
                    $areaData['notes'] ?? null,
                );
            }

            $process->update([
                'status' => 'dispatched_to_areas',
            ]);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'dispatch_to_areas',
                'Processo distribuído a ' . count($assignmentsData) . ' área(s)',
            );

            return $process->fresh(['currentDepartment', 'currentArea', 'assignments']);
        });
    }

    public function addTechnician(int $processId, int $assignmentId, int $userId, ?string $visibility = null): ProcessAssignment
    {
        return DB::transaction(function () use ($processId, $assignmentId, $userId, $visibility) {
            $assignment = ProcessAssignment::where('process_id', $processId)->findOrFail($assignmentId);

            $this->assignmentService->addTechnician($assignmentId, $userId, auth()->id());

            if ($visibility) {
                $assignment->update(['visibility' => $visibility]);
            }

            $this->registerMovement(
                $assignment->process,
                $assignment->department_id,
                $assignment->area_id,
                null,
                null,
                'add_technician',
                'Técnico adicionado à atribuição',
            );

            return $assignment->fresh(['technicians']);
        });
    }

    public function removeTechnician(int $processId, int $assignmentId, int $userId): ProcessAssignment
    {
        return DB::transaction(function () use ($processId, $assignmentId, $userId) {
            $assignment = ProcessAssignment::where('process_id', $processId)->findOrFail($assignmentId);

            $this->assignmentService->removeTechnician($assignmentId, $userId);

            return $assignment->fresh(['technicians']);
        });
    }

    public function makePublic(int $processId, int $assignmentId): ProcessAssignment
    {
        $assignment = ProcessAssignment::where('process_id', $processId)->findOrFail($assignmentId);
        $assignment->update(['visibility' => 'public']);

        $this->registerMovement(
            $assignment->process,
            $assignment->department_id,
            $assignment->area_id,
            null,
            null,
            'make_public',
            'Atribuição tornada pública para todos os técnicos da área',
        );

        return $assignment->fresh();
    }

    public function startByTechnician(int $processId, int $assignmentId, int $technicianId): ProcessAssignmentTechnician
    {
        return $this->assignmentService->startTechnician($assignmentId, $technicianId);
    }

    public function submitByTechnician(int $processId, int $assignmentId, int $technicianId, array $data = []): ProcessAssignmentTechnician
    {
        return $this->assignmentService->submitTechnician($assignmentId, $technicianId, $data);
    }

    public function validateAssignment(int $processId, int $assignmentId): ProcessAssignment
    {
        return DB::transaction(function () use ($processId, $assignmentId) {
            $assignment = ProcessAssignment::where('process_id', $processId)->findOrFail($assignmentId);

            $assignment->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => auth()->id(),
            ]);

            $this->registerMovement(
                $assignment->process,
                $assignment->department_id,
                $assignment->area_id,
                null,
                null,
                'validation_chief',
                'Atribuição validada pelo chefe',
            );

            $this->recalculateProcessStatus($assignment->process_id);

            return $assignment->fresh(['technicians', 'process']);
        });
    }

    public function correctionAssignment(int $processId, int $assignmentId, ?string $notes = null): ProcessAssignment
    {
        return DB::transaction(function () use ($processId, $assignmentId, $notes) {
            $assignment = ProcessAssignment::where('process_id', $processId)->findOrFail($assignmentId);

            $assignment->update([
                'status' => 'correction_requested',
                'notes' => $notes,
            ]);

            $this->registerMovement(
                $assignment->process,
                $assignment->department_id,
                $assignment->area_id,
                null,
                null,
                'correction',
                'Correção solicitada na atribuição',
            );

            $this->recalculateProcessStatus($assignment->process_id);

            return $assignment->fresh(['technicians']);
        });
    }

    public function validateByChief(int $processId): Process
    {
        return DB::transaction(function () use ($processId) {
            $process = $this->repository->show($processId);

            $allValidated = $process->assignments->every(
                fn ($a) => in_array($a->status, ['validated', 'completed'])
            );

            if (!$allValidated) {
                throw new \Exception('Nem todas as atribuições foram validadas.');
            }

            $process->update(['status' => 'validated_by_chief']);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'validation_chief',
                'Processo validado pelo chefe de departamento',
            );

            return $process->fresh(['currentDepartment', 'currentArea', 'assignments']);
        });
    }

    public function validateByDirector(int $processId): Process
    {
        return DB::transaction(function () use ($processId) {
            $process = $this->repository->show($processId);

            if ($process->status !== 'validated_by_chief') {
                throw new \Exception('Processo não está validado pelo chefe.');
            }

            $process->update(['status' => 'validated_by_director']);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'validation_director',
                'Processo validado pelo director',
            );

            return $process->fresh(['currentDepartment', 'currentArea']);
        });
    }

    public function requestCorrection(int $processId, ?string $notes = null): Process
    {
        return DB::transaction(function () use ($processId, $notes) {
            $process = $this->repository->show($processId);

            $process->update([
                'status' => 'correction_requested',
                'notes' => $notes,
            ]);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'correction',
                'Correção solicitada no processo',
            );

            return $process->fresh();
        });
    }

    public function reject(int $processId, ?string $reason = null): Process
    {
        return DB::transaction(function () use ($processId, $reason) {
            $process = $this->repository->show($processId);

            $process->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'rejection',
                'Processo rejeitado: ' . ($reason ?? 'Sem motivo'),
            );

            return $process->fresh();
        });
    }

    public function close(int $processId): Process
    {
        return DB::transaction(function () use ($processId) {
            $process = $this->repository->show($processId);

            $process->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);

            $this->registerMovement(
                $process,
                $process->current_department_id,
                $process->current_area_id,
                null,
                null,
                'closure',
                'Processo encerrado',
            );

            return $process->fresh(['currentDepartment', 'currentArea', 'closedBy']);
        });
    }

    protected function storeDocuments(int $processId, mixed $files, ?string $documentType = null): void
    {
        $single = $files instanceof UploadedFile;
        $files = $single ? [$files] : $files;

        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store($processId . '/process-documents', 'public');

                ProcessDocument::create([
                    'process_id' => $processId,
                    'document_type' => $documentType ?? $this->guessDocumentType($file),
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }

    protected function guessDocumentType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        return match (true) {
            str_contains($mime, 'pdf') => 'PDF',
            str_contains($mime, 'image') => 'Imagem',
            str_contains($mime, 'word') || str_contains($mime, 'document') => 'Documento Word',
            str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') => 'Folha de cálculo',
            str_contains($mime, 'text') => 'Texto',
            default => $mime,
        };
    }

    protected function registerMovement(
        Process $process,
        ?int $fromDeptId,
        ?int $fromAreaId,
        ?int $toDeptId,
        ?int $toAreaId,
        string $type,
        ?string $notes = null,
        ?string $attachment = null,
        ?int $toUserId = null,
    ): void {
        ProcessMovement::create([
            'process_id' => $process->id,
            'from_department_id' => $fromDeptId,
            'from_area_id' => $fromAreaId,
            'to_department_id' => $toDeptId,
            'to_area_id' => $toAreaId,
            'from_user_id' => auth()->id(),
            'to_user_id' => $toUserId,
            'movement_type' => $type,
            'notes' => $notes,
            'attachment_path' => $attachment,
        ]);
    }

    protected function findDepartmentChief(int $departmentId): int
    {
        $employee = \App\Models\RH\Employee\Employee::where('department_id', $departmentId)
            ->whereHas('position', fn ($q) => $q->where('level', '<=', 7))
            ->with('user')
            ->first();

        if ($employee && $employee->user) {
            return $employee->user->id;
        }

        return auth()->id();
    }

    protected function recalculateProcessStatus(int $processId): void
    {
        $process = Process::with('assignments')->findOrFail($processId);
        $assignments = $process->assignments;

        if ($assignments->isEmpty()) {
            return;
        }

        $anyCorrection = $assignments->contains('status', 'correction_requested');
        $anyProcessing = $assignments->contains('status', 'processing');
        $anyPending = $assignments->contains('status', 'pending');
        $allValidated = $assignments->every(
            fn ($a) => in_array($a->status, ['validated', 'completed'])
        );

        $newStatus = $process->status;

        if ($anyCorrection) {
            $newStatus = 'correction_requested';
        } elseif ($allValidated) {
            $newStatus = 'pending_validation';
        } elseif ($anyProcessing) {
            $newStatus = 'processing';
        } elseif ($anyPending) {
            $newStatus = 'dispatched_to_areas';
        }

        if ($newStatus !== $process->status) {
            $process->update(['status' => $newStatus]);
        }
    }
}
