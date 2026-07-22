<?php

namespace App\Http\Controllers\RH\Position;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Position\PositionRequest;
use App\Services\RH\Position\PositionService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PositionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Position';
    protected ?string $fieldName = 'name';

    public function __construct(PositionService $service)
    {
        $this->service = $service;
    }

    public function store(PositionRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $position = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Cargo ' . $position->name . ' criado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($position, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar cargo', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(PositionRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $position = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Cargo ' . $position->name . ' atualizado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($position, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar cargo', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
