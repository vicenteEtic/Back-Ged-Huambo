<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\AbsenceJustification;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Employee\Employee;
use App\Repositories\RH\Attendance\AbsenceJustificationRepository;
use App\Services\AbstractService;
use App\Services\RH\Leave\LeaveRequestService;
use App\Services\Upload\FileUploadService;
use App\Support\Dispensa;
use App\Support\PontoExceptions;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbsenceJustificationService extends AbstractService
{
    public function __construct(
        AbsenceJustificationRepository $repository,
        private FileUploadService $uploadService,
        private ?LeaveRequestService $leaveRequestService = null,
    ) {
        parent::__construct($repository);
    }

    public function store(array $data): AbsenceJustification
    {
        return DB::transaction(function () use ($data) {
            $data = $this->clean($data);

            $proof = $data['proof'] ?? null;
            unset($data['proof']);

            $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');

            if (! empty($data['employee_id']) && ($this->leaveRequestService?->isOnLeave((int) $data['employee_id'], $data['date']) ?? false)) {
                throw new DomainException('Funcionário de férias: não é permitido registar falta nesta data.');
            }

            if (! empty($data['employee_id']) && Dispensa::approvedFullDayForDate((int) $data['employee_id'], $data['date'])) {
                throw new DomainException('Funcionário com dispensa aprovada nesta data: não é permitido registar falta.');
            }

            $employee = Employee::with('department')->find($data['employee_id'] ?? null);

            if ($employee && PontoExceptions::isEmployeeExempt($employee)) {
                throw new DomainException('Gabinete com excepção no livro de ponto: este funcionário não assina o ponto no RH.');
            }

            $data['status'] = $data['status'] ?? 'pending';
            $data['submitted_by'] = auth()->id();

            $attendance = $this->resolveAttendance($data['employee_id'], $data['date'], $data['absence_type'] ?? null, $data['reason'] ?? null);
            $data['attendance_id'] = $attendance->id;

            if ($proof) {
                $result = $this->uploadService->processUploadedFile($proof, 'absence-justifications/'.$data['employee_id']);
                $data['proof_path'] = $result['path'];
            }

            $model = $this->repository->store($data);

            if ($data['status'] === 'approved') {
                $this->applyToAttendance($attendance, true, $data['absence_type'] ?? null, $data['reason'] ?? null);
            }

            return $model->fresh(['employee', 'attendance', 'submittedBy']);
        });
    }

    public function update(array $data, int $id): AbsenceJustification
    {
        return DB::transaction(function () use ($data, $id) {
            $data = $this->clean($data);
            $model = $this->repository->show($id);

            $proof = $data['proof'] ?? null;
            unset($data['proof']);

            if (isset($data['date'])) {
                $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
            }

            if ($proof) {
                if ($model->proof_path) {
                    Storage::disk('public')->delete($model->proof_path);
                }
                $result = $this->uploadService->processUploadedFile($proof, 'absence-justifications/'.$model->employee_id);
                $data['proof_path'] = $result['path'];
            }

            return $this->repository->update($data, $id);
        });
    }

    public function approve(int $id, ?string $reviewNotes = null): AbsenceJustification
    {
        return DB::transaction(function () use ($id, $reviewNotes) {
            $model = $this->repository->show($id);

            $model->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes ?? $model->review_notes,
            ]);

            $attendance = $model->attendance;
            if ($attendance) {
                $this->applyToAttendance($attendance, true, $model->absence_type, $model->reason);
            }

            return $model->fresh(['employee', 'attendance', 'reviewedBy']);
        });
    }

    public function reject(int $id, ?string $reviewNotes = null): AbsenceJustification
    {
        return DB::transaction(function () use ($id, $reviewNotes) {
            $model = $this->repository->show($id);

            $model->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes ?? $model->review_notes,
            ]);

            $attendance = $model->attendance;
            if ($attendance) {
                $attendance->update([
                    'is_justified' => false,
                    'absence_type' => $attendance->absence_type,
                ]);
            }

            return $model->fresh(['employee', 'attendance', 'reviewedBy']);
        });
    }

    public function destroy(int $id): void
    {
        $model = $this->repository->show($id);

        if ($model->proof_path) {
            Storage::disk('public')->delete($model->proof_path);
        }

        $this->repository->destroy($id);
    }

    private function resolveAttendance(int $employeeId, string $date, ?string $absenceType = null, ?string $reason = null): Attendance
    {
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if ($attendance) {
            return $attendance;
        }

        return Attendance::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'status' => 'absent',
            'absence_type' => $absenceType,
            'absence_reason' => $reason,
            'is_justified' => false,
        ]);
    }

    private function applyToAttendance(Attendance $attendance, bool $justified, ?string $absenceType = null, ?string $reason = null): void
    {
        $attendance->update([
            'is_justified' => $justified,
            'absence_type' => $absenceType ?? $attendance->absence_type,
            'absence_reason' => $reason ?? $attendance->absence_reason,
        ]);
    }
}
