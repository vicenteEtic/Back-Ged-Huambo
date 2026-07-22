<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\ShiftAssignmentRequest;
use App\Services\RH\Attendance\ShiftAssignmentService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ShiftAssignmentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Alocação de Turno';
    protected ?string $fieldName = 'id';

    public function __construct(ShiftAssignmentService $service)
    {
        $this->service = $service;
    }

    public function store(ShiftAssignmentRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            $model = $this->service->store($data);
            return $model->load('shift');
        });
    }

    public function update(ShiftAssignmentRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $model = $this->service->update($request->validated(), $id);
            return $model->load('shift');
        }, $id);
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
            Log::error('Erro ao buscar alocações', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
