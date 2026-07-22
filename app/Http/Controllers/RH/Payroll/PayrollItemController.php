<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollItemRequest;
use App\Services\RH\Payroll\PayrollItemService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollItemController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'PayrollItem';
    protected ?string $fieldName = 'id';

    public function __construct(PayrollItemService $service)
    {
        $this->service = $service;
    }

    public function store(PayrollItemRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $item = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Item de folha de pagamento criado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($item, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar item de folha de pagamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(PayrollItemRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $item = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Item de folha de pagamento atualizado por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($item, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar item de folha de pagamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
