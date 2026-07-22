<?php

namespace App\Http\Controllers\RH\Career;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Career\ProgressionRequestRequest;
use App\Services\RH\Career\ProgressionRequestService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgressionRequestController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Solicitação de Progressão';
    protected ?string $fieldName = 'id';

    public function __construct(
        ProgressionRequestService $service,
        protected ProgressionRequestService $progressionService
    ) {
        $this->service = $service;
    }

    public function store(ProgressionRequestRequest $request)
    {
        return $this->handleStore(
            fn() => $this->progressionService->submit($request->validated()),
        );
    }

    public function update(ProgressionRequestRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function approve(Request $request, int $id)
    {
        try {
            $request->validate(['comment' => 'nullable|string']);
            $model = $this->progressionService->approve($id, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao aprovar progressão', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function reject(Request $request, int $id)
    {
        try {
            $request->validate(['comment' => 'required|string']);
            $model = $this->progressionService->reject($id, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao rejeitar progressão', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function execute(int $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->progressionService->execute($id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Solicitação de progressão #' . $model->id . ' executada por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($model->load(['employee', 'rule', 'employee.functionalHistory']));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao executar progressão', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function historyByEmployee(int $employeeId)
    {
        try {
            $models = $this->service->index(
                request('paginate'),
                ['employee_id' => $employeeId] + (request('filters') ?? []),
                request('orderBy'),
                ['employee', 'rule', 'approvals']
            );
            return response()->json($models);
        } catch (Exception $e) {
            Log::error('Erro ao buscar histórico de progressão', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
