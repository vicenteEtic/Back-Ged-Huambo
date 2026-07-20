<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollPeriodRequest;
use App\Services\RH\Payroll\PayrollPeriodService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollPeriodController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'PayrollPeriod';
    protected ?string $fieldName = 'name';

    public function __construct(PayrollPeriodService $service)
    {
        $this->service = $service;
    }

    public function store(PayrollPeriodRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $period = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Payroll period ' . $period->name . ' created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($period, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating payroll period', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(PayrollPeriodRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $period = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Payroll period ' . $period->name . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($period, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating payroll period', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
