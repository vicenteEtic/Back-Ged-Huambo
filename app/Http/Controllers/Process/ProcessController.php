<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\Process\ProcessRequest;
use App\Http\Requests\Process\ProcessDispatchRequest;
use App\Http\Requests\Process\ProcessDispatchAreasRequest;
use App\Http\Requests\Process\ProcessAddTechnicianRequest;
use App\Http\Requests\Process\ProcessSubmitRequest;
use App\Http\Requests\Process\ProcessCommentRequest;
use App\Models\Process\ProcessAssignment;
use App\Services\Process\ProcessService;
use App\Services\Process\ProcessMovementService;
use App\Services\Process\ProcessCommentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessController extends AbstractController
{
    protected ?string $logType = 'process';
    protected ?string $nameEntity = 'Process';
    protected ?string $fieldName = 'subject';

    public function __construct(
        ProcessService $service,
        protected ProcessMovementService $movementService,
        protected ProcessCommentService $commentService,
    ) {
        $this->service = $service;
    }

    // === CRUD ===

    public function store(ProcessRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'process',
                level: 'info',
                customMessage: 'Processo ' . $process->sequence_number . ' criado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($process, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating process', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ProcessRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($process->fresh(['currentDepartment', 'currentArea', 'currentHolder']), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating process', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // === WORKFLOW: Expediente → Chefe ===

    public function dispatchToChief(ProcessDispatchRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->dispatchToChief($id, $request->department_id, $request->area_id ?? null);
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === WORKFLOW: Chefe distribui a áreas ===

    public function dispatchToAreas(ProcessDispatchAreasRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->dispatchToAreas($id, $request->validated()['assignments']);
            DB::commit();
            return response()->json($process->load('assignments.technicians'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === TÉCNICOS ===

    public function addTechnician(ProcessAddTechnicianRequest $request, int $id, int $assignmentId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $assignment = $this->service->addTechnician($id, $assignmentId, $request->user_id, $request->visibility ?? null);
            DB::commit();
            return response()->json($assignment->load('technicians'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function removeTechnician(int $id, int $assignmentId, int $userId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $this->service->removeTechnician($id, $assignmentId, $userId);
            DB::commit();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function makePublic(int $id, int $assignmentId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $assignment = $this->service->makePublic($id, $assignmentId);
            DB::commit();
            return response()->json($assignment);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === TRATAMENTO POR TÉCNICO ===

    public function startByTechnician(int $id, int $assignmentId, int $technicianId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $tech = $this->service->startByTechnician($id, $assignmentId, $technicianId);
            DB::commit();
            return response()->json($tech);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function submitByTechnician(ProcessSubmitRequest $request, int $id, int $assignmentId, int $technicianId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $tech = $this->service->submitByTechnician($id, $assignmentId, $technicianId, $request->validated());
            DB::commit();
            return response()->json($tech);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === VALIDAÇÃO DE ASSIGNMENT ===

    public function validateAssignment(int $id, int $assignmentId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $assignment = $this->service->validateAssignment($id, $assignmentId);
            DB::commit();
            return response()->json($assignment->load('process'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function correctionAssignment(int $id, int $assignmentId, ?string $notes = null)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $assignment = $this->service->correctionAssignment($id, $assignmentId, $notes);
            DB::commit();
            return response()->json($assignment);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === VALIDAÇÃO ENCADEADA ===

    public function validateByChief(int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->validateByChief($id);
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function validateByDirector(int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->validateByDirector($id);
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function requestCorrection(int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->requestCorrection($id, request('notes'));
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function reject(int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->reject($id, request('reason'));
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function close(int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $process = $this->service->close($id);
            DB::commit();
            return response()->json($process);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // === LISTAGENS ===

    public function inbox()
    {
        try {
            $this->logRequest();
            $result = $this->service->inbox(auth()->id(), auth()->user()->role_id);
            return response()->json($result);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Error fetching inbox', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function outbox()
    {
        try {
            $this->logRequest();
            $result = $this->service->outbox(auth()->id());
            return response()->json($result);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Error fetching outbox', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function history()
    {
        try {
            $this->logRequest();
            $result = $this->service->history();
            return response()->json($result);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Error fetching history', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // === DADOS AUXILIARES ===

    public function movements(int $id)
    {
        try {
            $movements = $this->movementService->byProcess($id);
            return response()->json($movements);
        } catch (Exception $e) {
            Log::error('Error fetching movements', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function comments(int $id)
    {
        try {
            $comments = $this->commentService->byProcess($id);
            return response()->json($comments);
        } catch (Exception $e) {
            Log::error('Error fetching comments', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeComment(ProcessCommentRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['process_id'] = $id;
            $data['user_id'] = auth()->id();
            $comment = $this->commentService->store($data);
            DB::commit();
            return response()->json($comment->load('user'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating comment', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listAssignments(int $id)
    {
        try {
            $process = $this->service->show($id);
            return response()->json($process->assignments->load(['technicians', 'department', 'area']));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error fetching assignments', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
