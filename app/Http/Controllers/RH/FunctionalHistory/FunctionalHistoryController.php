<?php

namespace App\Http\Controllers\RH\FunctionalHistory;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\FunctionalHistory\FunctionalHistoryRequest;
use App\Services\RH\FunctionalHistory\FunctionalHistoryService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FunctionalHistoryController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'FunctionalHistory';
    protected ?string $fieldName = 'id';

    public function __construct(FunctionalHistoryService $service)
    {
        $this->service = $service;
    }

    public function store(FunctionalHistoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            $model = $this->service->store($data);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Functional history #' . $model->id . ' created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($model, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating functional history', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(FunctionalHistoryRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $model = $this->service->update($data, $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Functional history #' . $model->id . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($model, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating functional history', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
