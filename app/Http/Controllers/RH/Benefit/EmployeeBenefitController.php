<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\EmployeeBenefitRequest;
use App\Services\RH\Benefit\EmployeeBenefitService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EmployeeBenefitController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'EmployeeBenefit';
    protected ?string $fieldName = 'id';
    public function __construct(EmployeeBenefitService $service) { $this->service = $service; }

    public function store(EmployeeBenefitRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Employee benefit created by ' . Auth::user()->first_name);
            DB::commit();
            return response()->json($model, Response::HTTP_CREATED);
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), '1062')) {
                return response()->json(['error' => 'Já existe um benefício para este funcionário, tipo e data de início.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            DB::rollBack(); $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EmployeeBenefitRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Employee benefit updated by ' . Auth::user()->first_name);
            DB::commit();
            return response()->json($model, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack(); return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), '1062')) {
                return response()->json(['error' => 'Já existe um benefício para este funcionário, tipo e data de início.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            DB::rollBack(); $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
