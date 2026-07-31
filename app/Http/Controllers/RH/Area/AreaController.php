<?php

namespace App\Http\Controllers\RH\Area;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Area\AreaRequest;
use App\Services\RH\Area\AreaService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AreaController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Área';
    protected ?string $fieldName = 'name';

    public function __construct(AreaService $service)
    {
        $this->service = $service;
    }

    public function store(AreaRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $area = $this->service->store($request->validated());
            return $area->load('department');
        });
    }

    public function update(AreaRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $area = $this->service->update($request->validated(), $id);
            return $area->load('department');
        }, $id);
    }

    public function byDepartment(int $departmentId)
    {
        try {
            $areas = $this->service->index(null, [['department_id' => ['filterValue' => $departmentId]]], null, ['department', 'responsible']);
            return response()->json($areas);
        } catch (Exception $e) {
            Log::error('Erro ao buscar áreas por departamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
