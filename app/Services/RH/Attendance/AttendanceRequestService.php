<?php

namespace App\Services\RH\Attendance;

use App\Enum\AttendanceRequestStatus;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\AttendanceRequest;
use App\Models\RH\Attendance\AttendanceRequestDocument;
use App\Models\RH\Attendance\AttendanceRequestLog;
use App\Models\RH\Attendance\AttendanceRequestType;
use App\Repositories\RH\Attendance\AttendanceRequestRepository;
use App\Repositories\RH\Attendance\AttendanceRequestTypeRepository;
use App\Services\AbstractService;
use App\Services\Upload\FileUploadService;
use App\Support\Dispensa;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceRequestService extends AbstractService
{
    protected AttendanceRequestTypeRepository $typeRepository;

    protected FileUploadService $upload;

    protected DespachoPdfService $despacho;

    public function __construct(
        AttendanceRequestRepository $repository,
        AttendanceRequestTypeRepository $typeRepository,
        FileUploadService $upload,
        DespachoPdfService $despacho,
    ) {
        parent::__construct($repository);
        $this->typeRepository = $typeRepository;
        $this->upload = $upload;
        $this->despacho = $despacho;
    }

    /**
     * Lista de tipos de solicitação activos (base de dados → attendance_request_types;
     * a tabela é semeada a partir de config/rh.php → dispensa.types).
     */
    public function types(): array
    {
        return collect(Dispensa::typeRegistry(activeOnly: true))->map(function ($type) {
            return [
                'id' => $type['id'] ?? null,
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => $type['description'] ?? null,
                'legal_ref' => $type['legal_ref'] ?? null,
                'max_days' => $type['max_days'] ?? null,
                'required_documents' => array_values((array) ($type['required_documents'] ?? [])),
                'required_document_names' => collect((array) ($type['required_documents'] ?? []))
                    ->map(fn ($code) => Dispensa::documentLabels()[$code] ?? $code)
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    public function statuses(): array
    {
        return collect(AttendanceRequestStatus::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ])->values()->all();
    }

    public function documentLabels(): array
    {
        return Dispensa::documentLabels();
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $filters = $filterParams ?? [];

        $query = AttendanceRequest::query()
            ->with(['employee', 'type', 'documents'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['date'])) {
            $date = Carbon::parse($filters['date'])->toDateString();
            $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $models = $paginate ? $query->paginate($paginate) : $query->get();

        return $models;
    }

    public function showWithRelations(int $id)
    {
        return AttendanceRequest::with([
            'employee',
            'employee.department',
            'type',
            'documents',
            'logs.user',
            'requester',
            'reviewer',
            'decidedBy',
        ])->findOrFail($id);
    }

    /**
     * Cria uma solicitação de dispensa com validação de período, documentos
     * obrigatórios e regra de amamentação (18 meses).
     */
    public function create(array $data, array $files = [], ?int $userId = null): AttendanceRequest
    {
        return DB::transaction(function () use ($data, $files, $userId) {
            $type = $this->resolveType($data['type_code'] ?? ($data['attendance_request_type_id'] ?? null));

            $data = $this->clean($data);
            $data = $this->applyTypeBenefits($type, $data);

            $this->assertPeriod($data);
            $this->assertMaxDays($type, $data);
            $this->assertNotOnLeave((int) $data['employee_id'], $data['start_date'], $data['end_date']);
            $this->assertNoOverlap((int) $data['employee_id'], $data['start_date'], $data['end_date']);
            $this->assertDocuments(array_values($files), Dispensa::requiredDocuments($data['type_code']));

            $typeModel = $this->syncType($type);

            $request = $this->repository->store([
                'request_number' => $this->nextRequestNumber(now()->year),
                'employee_id' => $data['employee_id'],
                'attendance_request_type_id' => $typeModel->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'applies_full_day' => $data['applies_full_day'] ?? true,
                'reason' => $data['reason'] ?? null,
                'description' => $data['description'] ?? null,
                'oversight_note' => $data['oversight_note'] ?? null,
                'status' => AttendanceRequestStatus::Pending->value,
                'benefit_start_date' => $data['benefit_start_date'] ?? null,
                'benefit_until' => $data['benefit_until'] ?? null,
                'benefit_active' => $data['benefit_active'] ?? true,
                'requested_by' => $userId,
            ]);

            $this->storeDocuments($request->id, $request->request_number, array_values($files), $userId);
            $this->logAction($request->id, 'created', null, 'pending', 'Solicitação criada.', $userId);

            return $this->showWithRelations($request->id);
        }, 6);
    }

    /**
     * Actualiza uma solicitação pendente. Solicitações já decididas são imutáveis.
     */
    public function update(array $data, int $id, array $files = [], ?int $userId = null): AttendanceRequest
    {
        return DB::transaction(function () use ($data, $id, $files, $userId) {
            $request = AttendanceRequest::with('documents')->findOrFail($id);
            $this->assertEditable($request);

            $typeCode = $data['type_code'] ?? $request->type?->code;
            $type = $this->resolveType($typeCode);

            // Actualizações parciais: completar com valores actuais
            $data['employee_id'] = $data['employee_id'] ?? $request->employee_id;
            $data['start_date'] = $data['start_date'] ?? $request->start_date->toDateString();
            $data['end_date'] = $data['end_date'] ?? $request->end_date->toDateString();

            if ($typeCode === 'amamentacao' && empty($data['benefit_start_date'])) {
                $data['benefit_start_date'] = $request->benefit_start_date?->toDateString();
            }

            $data = $this->clean($data);
            $data = $this->applyTypeBenefits($type, $data);

            $this->assertPeriod($data);
            $this->assertMaxDays($type, $data);
            $this->assertNotOnLeave((int) $data['employee_id'], $data['start_date'], $data['end_date']);
            $this->assertNoOverlap((int) $data['employee_id'], $data['start_date'], $data['end_date'], $request->id);

            $existingDocTypes = $request->documents->pluck('document_type')->filter()->values()->all();
            $required = Dispensa::requiredDocuments($data['type_code'] ?? $typeCode);
            $remaining = array_values(array_diff($required, $existingDocTypes));

            $this->assertDocuments(array_values($files), $remaining);

            $typeModel = $this->syncType($type);

            $this->repository->update([
                'employee_id' => $data['employee_id'],
                'attendance_request_type_id' => $typeModel->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'applies_full_day' => $data['applies_full_day'] ?? $request->applies_full_day,
                'reason' => $data['reason'] ?? $request->reason,
                'description' => $data['description'] ?? $request->description,
                'oversight_note' => $data['oversight_note'] ?? $request->oversight_note,
                'benefit_start_date' => $data['benefit_start_date'] ?? $request->benefit_start_date,
                'benefit_until' => $data['benefit_until'] ?? $request->benefit_until,
                'benefit_active' => $data['benefit_active'] ?? $request->benefit_active,
            ], $id);

            $this->storeDocuments($request->id, $request->request_number, array_values($files), $userId);
            $this->logAction($request->id, 'updated', $request->status, $request->status, 'Solicitação actualizada.', $userId);

            return $this->showWithRelations($id);
        }, 6);
    }

    public function destroy(int $id, ?int $userId = null): void
    {
        $request = AttendanceRequest::with('documents')->findOrFail($id);
        $this->assertEditable($request, allowCancelled: false);

        foreach ($request->documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $this->repository->destroy($id);
        $this->logAction($id, 'deleted', $request->status, null, 'Solicitação eliminada.', $userId);
    }

    public function markUnderReview(int $id, ?int $userId = null): AttendanceRequest
    {
        return DB::transaction(function () use ($id, $userId) {
            $request = AttendanceRequest::findOrFail($id);

            if ($request->status !== AttendanceRequestStatus::Pending->value) {
                throw new DomainException('Apenas solicitações pendentes podem ser marcadas como em análise.');
            }

            $this->repository->update([
                'status' => AttendanceRequestStatus::UnderReview->value,
                'reviewed_by' => $userId ?: $request->reviewed_by,
            ], $id);

            $this->logAction($id, 'under_review', 'pending', 'under_review', 'Solicitação em análise.', $userId);

            return $this->showWithRelations($id);
        }, 6);
    }

    public function approve(int $id, ?int $userId = null, ?string $note = null): AttendanceRequest
    {
        return DB::transaction(function () use ($id, $userId, $note) {
            $request = AttendanceRequest::findOrFail($id);

            if (! in_array($request->status, [
                AttendanceRequestStatus::Pending->value,
                AttendanceRequestStatus::UnderReview->value,
            ], true)) {
                throw new DomainException('Esta solicitação já foi decidida.');
            }

            $fromStatus = $request->status;

            $data = [
                'status' => AttendanceRequestStatus::Approved->value,
                'decided_by' => $userId,
                'decided_at' => now(),
                'decision_note' => $note,
                'despacho_decision' => 'approved',
                'despacho_number' => $this->nextDespachoNumber(),
            ];

            $this->repository->update($data, $id);

            $request = $this->showWithRelations($id);
            $request->despacho_path = $this->despacho->generate($request);
            $request->save();

            $this->applyToAttendance($request);
            $this->logAction($id, 'approved', $fromStatus, 'approved', $note ?: 'Solicitação aprovada.', $userId);
            $this->logAction($id, 'despacho', null, null, 'Despacho emitido.', $userId);

            return $this->showWithRelations($id);
        }, 6);
    }

    public function reject(int $id, string $note, ?int $userId = null): AttendanceRequest
    {
        return DB::transaction(function () use ($id, $note, $userId) {

            $request = AttendanceRequest::findOrFail($id);

            if (! in_array($request->status, [
                AttendanceRequestStatus::Pending->value,
                AttendanceRequestStatus::UnderReview->value,
            ], true)) {
                throw new DomainException('Esta solicitação já foi decidida.');
            }

            $fromStatus = $request->status;

            $data = [
                'status' => AttendanceRequestStatus::Rejected->value,
                'decided_by' => $userId,
                'decided_at' => now(),
                'decision_note' => $note,
                'despacho_decision' => 'rejected',
                'despacho_number' => $this->nextDespachoNumber(),
            ];

            $this->repository->update($data, $id);

            $request = $this->showWithRelations($id);
            $request->despacho_path = $this->despacho->generate($request);
            $request->save();

            $this->logAction($id, 'rejected', $fromStatus, 'rejected', $note, $userId);
            $this->logAction($id, 'despacho', null, null, 'Despacho emitido.', $userId);

            return $this->showWithRelations($id);
        }, 6);
    }

    public function cancel(int $id, ?int $userId = null, ?string $note = null): AttendanceRequest
    {
        return DB::transaction(function () use ($id, $userId, $note) {
            $request = AttendanceRequest::findOrFail($id);

            if (in_array($request->status, [
                AttendanceRequestStatus::Approved->value,
                AttendanceRequestStatus::Rejected->value,
            ], true)) {
                throw new DomainException('Solicitações aprovadas ou rejeitadas não podem ser canceladas.');
            }

            $fromStatus = $request->status;

            $this->repository->update([
                'status' => AttendanceRequestStatus::Cancelled->value,
                'decision_note' => $note ?: $request->decision_note,
            ], $id);

            $this->logAction($id, 'cancelled', $fromStatus, 'cancelled', $note ?: 'Solicitação cancelada.', $userId);

            return $this->showWithRelations($id);
        }, 6);
    }

    public function despatchedFile(int $id): array
    {
        $request = AttendanceRequest::findOrFail($id);

        if (! $request->despacho_path) {
            $request->despacho_path = $this->despacho->generate($request);
            $request->save();
        }

        return [
            'path' => $request->despacho_path,
            'url' => Storage::disk('public')->url($request->despacho_path),
            'filename' => basename($request->despacho_path),
        ];
    }

    public function downloadDespacho(int $id)
    {
        $request = AttendanceRequest::findOrFail($id);

        if (! $request->despacho_path || ! Storage::disk('public')->exists($request->despacho_path)) {
            throw new DomainException('Despacho ainda não foi emitido para esta solicitação.');
        }

        return Storage::disk('public')->download($request->despacho_path, basename($request->despacho_path));
    }

    public function downloadDocument(int $requestId, int $documentId)
    {
        $document = AttendanceRequestDocument::where('attendance_request_id', $requestId)
            ->findOrFail($documentId);

        if (! Storage::disk('public')->exists($document->file_path)) {
            throw new DomainException('Ficheiro não encontrado.');
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /**
     * Aplica a dispensa aprovada à assiduidade: cria registos com estado
     * 'Dispensado' para cada dia útil coberto (dispensa de dia inteiro).
     * A dispensa parcial (amamentação) não cria registos — reduz apenas o
     * horário quando a funcionária regista a entrada.
     */
    public function applyToAttendance(AttendanceRequest $request): void
    {
        if (! $request->applies_full_day) {
            return;
        }

        $day = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        while ($day->lte($end)) {
            $date = $day->toDateString();

            if ($day->isWeekend()) {
                $day->addDay();

                continue;
            }

            $record = Attendance::firstOrNew([
                'employee_id' => $request->employee_id,
                'date' => $date,
            ]);

            if ($record->exists && $record->status === 'present') {
                if (! $record->attendance_request_id) {
                    $record->fill(['attendance_request_id' => $request->id])->save();
                }
            } else {
                $record->fill([
                    'attendance_request_id' => $request->id,
                    'status' => 'dispensado',
                    'is_justified' => true,
                    'absence_type' => null,
                    'absence_reason' => $request->reason,
                    'notes' => 'Dispensa aprovada ('.$request->type?->name.').',
                    'hours_worked' => 0,
                ])->save();
            }

            $day->addDay();
        }
    }

    /**
     * Expira benefícios de amamentação que ultrapassaram os 18 meses.
     */
    public function expireBreastfeedingBenefits(): array
    {
        $expired = AttendanceRequest::query()
            ->whereHas('type', fn ($q) => $q->where('code', 'amamentacao'))
            ->where('status', 'approved')
            ->where('benefit_active', true)
            ->whereNotNull('benefit_until')
            ->whereDate('benefit_until', '<', now()->toDateString())
            ->get();

        foreach ($expired as $request) {
            $request->update(['benefit_active' => false]);
            $this->logAction($request->id, 'expired', 'approved', 'approved', 'Benefício de amamentação terminado após 18 meses.', null);
        }

        return [
            'expired' => $expired->count(),
        ];
    }

    // ---------------------------------------------------------------------
    // Validações
    // ---------------------------------------------------------------------

    protected function resolveType(mixed $code): array
    {
        $code = (string) ($code instanceof AttendanceRequestType ? $code->code : $code);

        $type = Dispensa::typeByCode($code);

        if (! $type) {
            throw new DomainException('Tipo de solicitação inválido.');
        }

        return $type;
    }

    protected function assertPeriod(array $data): void
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end < $start) {
            throw new DomainException('A data inicial não pode ser posterior à data final.');
        }
    }

    protected function assertMaxDays(array $type, array $data): void
    {
        $maxDays = $type['max_days'] ?? null;

        if ($maxDays === null) {
            return;
        }

        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        if ($days > $maxDays) {
            $message = config("rh.dispensa.max_days_messages.{$maxDays}");

            throw new DomainException($message ?: "O período máximo para este tipo de solicitação é de {$maxDays} dias.");
        }
    }

    protected function assertNotOnLeave(int $employeeId, string $start, string $end): void
    {
        $overlap = \App\Models\RH\Leave\LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            throw new DomainException('Funcionário de férias no período indicado: não é possível solicitar dispensa durante férias.');
        }
    }

    protected function assertNoOverlap(int $employeeId, string $start, string $end, ?int $ignoreId = null): void
    {
        $overlap = $this->repository->findOverlappingApproved($employeeId, $start, $end, $ignoreId);

        if ($overlap) {
            throw new DomainException("Período sobreposto à solicitação {$overlap->request_number}.");
        }
    }

    protected function assertDocuments(array $files, array $requiredCodes): void
    {
        if (empty($requiredCodes)) {
            return;
        }

        $submitted = collect($files)->pluck('type')->filter()->map(fn ($type) => (string) $type)->values();

        $missing = collect($requiredCodes)->reject(fn ($code) => $submitted->contains($code));

        if ($missing->isNotEmpty()) {
            $names = $missing->map(fn ($code) => Dispensa::documentLabels()[$code] ?? $code)->implode(', ');

            throw new DomainException("Documentos obrigatórios em falta: {$names}.");
        }
    }

    /**
     * Aplica regras específicas do tipo de solicitação:
     * - amamentação: apenas dispensa parcial (redução diária), 18 meses após o
     *   nascimento (benefit_until calculado em backend).
     */
    protected function applyTypeBenefits(array $type, array $data): array
    {
        $code = $type['code'];

        if ($code === 'amamentacao') {
            $data['applies_full_day'] = false;

            if (empty($data['benefit_start_date'])) {
                throw new DomainException('A data de nascimento da criança é obrigatória na dispensa para amamentação.');
            }

            $birth = Carbon::parse($data['benefit_start_date']);
            $until = Dispensa::breastfeedingUntil($birth->toDateString());
            $data['benefit_until'] = $until;
            $data['benefit_active'] = true;

            if (Carbon::parse($data['end_date']) > Carbon::parse($until)) {
                throw new DomainException("O benefício de amamentação termina em {$until}; o período solicitado excede o prazo legal de 18 meses.");
            }
        }

        return $data;
    }

    protected function assertEditable(AttendanceRequest $request, bool $allowCancelled = true): void
    {
        if ($request->isDecided()) {
            if ($request->status === AttendanceRequestStatus::Cancelled->value && $allowCancelled) {
                return;
            }

            throw new DomainException('Solicitação já decidida: não é possível alterar.');
        }
    }

    // ---------------------------------------------------------------------
    // Apoio
    // ---------------------------------------------------------------------

    protected function syncType(array $type): AttendanceRequestType
    {
        $typeModel = $this->typeRepository->firstByCode($type['code']);

        if ($typeModel) {
            return $typeModel;
        }

        return $this->typeRepository->store([
            'code' => $type['code'],
            'name' => $type['name'],
            'description' => $type['description'] ?? null,
            'required_documents' => $type['required_documents'] ?? [],
            'max_days' => $type['max_days'] ?? null,
            'legal_ref' => $type['legal_ref'] ?? null,
            'is_active' => true,
            'sort_order' => $type['sort_order'] ?? 0,
        ]);
    }

    protected function storeDocuments(int $requestId, string $requestNumber, array $files, ?int $userId): void
    {
        $directory = 'dispensas/'.str_replace('/', '-', $requestNumber);

        foreach ($files as $file) {
            $uploaded = $file['file'] ?? $file;
            if (! $uploaded instanceof UploadedFile) {
                continue;
            }

            $result = $this->upload->processUploadedFile($uploaded, $directory);

            AttendanceRequestDocument::create([
                'attendance_request_id' => $requestId,
                'document_type' => $file['type'] ?? null,
                'original_name' => $result['original_name'],
                'file_path' => $result['path'],
                'mime_type' => $result['mime_type'] ?? null,
                'size' => $result['final_size'] ?? null,
                'uploaded_by' => $userId,
            ]);
        }
    }

    protected function logAction(int $requestId, string $action, ?string $from, ?string $to, ?string $note, ?int $userId): void
    {
        AttendanceRequestLog::create([
            'attendance_request_id' => $requestId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => $userId,
        ]);
    }

    protected function nextRequestNumber(int $year): string
    {
        $count = AttendanceRequest::withTrashed()
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('RD/%04d/%d', $count + 1, $year);
    }

    protected function nextDespachoNumber(): string
    {
        $year = now()->year;
        $count = AttendanceRequest::withTrashed()
            ->whereYear('decided_at', $year)
            ->whereNotNull('despacho_number')
            ->count();

        return sprintf('DSP/%04d/%d', $count + 1, $year);
    }
}
