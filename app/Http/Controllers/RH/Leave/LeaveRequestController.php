<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeaveRequestForm;
use App\Services\RH\Leave\LeaveRequestService;
use App\Models\RH\Leave\LeaveRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use DomainException;

class LeaveRequestController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Pedido de Férias';
    protected ?string $fieldName = 'id';

    public function __construct(
        LeaveRequestService $service,
        protected LeaveRequestService $leaveService
    ) {
        $this->service = $service;
    }

    public function store(LeaveRequestForm $request)
    {
        return $this->handleStore(function () use ($request) {
            $leaveRequest = $this->leaveService->submit($request->validated());
            return $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Pedido de férias #' . $leaveRequest->id . ' submetido por ' . auth()->user()->first_name
            ) ? $leaveRequest : $leaveRequest;
        });
    }

    public function update(LeaveRequestForm $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function balance(int $employeeId)
    {
        try {
            $year = request('year', now()->year);
            $leaveTypeId = request('leave_type_id');
            return response()->json($this->leaveService->balanceByEmployee($employeeId, $year, $leaveTypeId));
        } catch (Exception $e) {
            Log::error('Erro ao obter saldo de férias', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->logRequest();
            $leaveRequest = LeaveRequest::findOrFail($id);

            if ($leaveRequest->status === 'approved') {
                return response()->json(['error' => 'Não é possível eliminar férias já aprovadas.'], Response::HTTP_FORBIDDEN);
            }

            $this->service->destroy($id);

            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: "Pedido de férias #{$id} removido por " . auth()->user()->first_name
            );

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Erro ao eliminar pedido de férias', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
