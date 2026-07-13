<?php

namespace App\Http\Controllers\RH\Area;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Area\AreaRequest;
use App\Services\RH\Area\AreaService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AreaController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Area';
    protected ?string $fieldName = 'name';

    public function __construct(AreaService $service)
    {
        $this->service = $service;
    }

    public function store(AreaRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $area = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Área ' . $area->name . ' criada por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($area->load('department'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating area', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(AreaRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $area = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Área ' . $area->name . ' actualizada por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($area->load('department'), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating area', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byDepartment(int $departmentId)
    {
        try {
            $areas = $this->service->index(null, [['department_id' => ['filterValue' => $departmentId]]], null, ['department', 'responsible']);
            return response()->json($areas);
        } catch (Exception $e) {
            Log::error('Error fetching areas by department', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
