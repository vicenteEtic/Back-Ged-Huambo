<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\IrtBracketRequest;
use App\Services\RH\Payroll\IrtBracketService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IrtBracketController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'IrtBracket';
    protected ?string $fieldName = 'bracket';

    public function __construct(IrtBracketService $service)
    {
        $this->service = $service;
    }

    public function store(IrtBracketRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Escalão IRT #' . $model->bracket . ' criado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($model, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar escalão IRT', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function update(IrtBracketRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Escalão IRT #' . $model->bracket . ' atualizado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($model, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar escalão IRT', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
