<?php

namespace App\Http\Controllers\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
use App\Services\RH\Leave\LeaveApprovalService;
use DomainException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LeaveApprovalController
{
    public function __construct(
        protected LeaveApprovalService $approvalService,
    ) {}

    public function approve(Request $request, int $leaveRequestId)
    {
        try {
            $request->validate(['comment' => 'nullable|string']);
            $model = $this->approvalService->approve($leaveRequestId, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao aprovar pedido de férias', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject(Request $request, int $leaveRequestId)
    {
        try {
            $request->validate(['comment' => 'required|string']);
            $model = $this->approvalService->reject($leaveRequestId, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao rejeitar pedido de férias', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pending()
    {
        try {
            $pending = LeaveRequest::with(['employee', 'leaveType'])
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();
            return response()->json($pending);
        } catch (Exception $e) {
            Log::error('Erro ao listar pedidos de férias pendentes', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
