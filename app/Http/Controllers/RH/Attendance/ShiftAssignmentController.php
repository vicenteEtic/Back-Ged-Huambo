<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\ShiftAssignmentRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftAssignmentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'ShiftAssignment';
    protected ?string $fieldName = 'id';

    public function __construct(\App\Services\RH\Attendance\ShiftAssignmentService $service)
    {
        $this->service = $service;
    }

    public function store(ShiftAssignmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            $model = $this->service->store($data);
            DB::commit();
            return response()->json($model->load('shift'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error assigning shift', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ShiftAssignmentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($model->load('shift'), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating shift assignment', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byEmployee(int $employeeId)
    {
        try {
            $models = $this->service->index(
                request('paginate'),
                ['employee_id' => $employeeId],
                request('orderBy'),
                ['shift']
            );
            return response()->json($models);
        } catch (Exception $e) {
            Log::error('Error fetching assignments', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
