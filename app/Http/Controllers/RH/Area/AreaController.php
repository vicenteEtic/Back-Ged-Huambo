<?php

namespace App\Http\Controllers\RH\Area;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Area\AreaRequest;
use App\Services\RH\Area\AreaService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            return $area->load(['departments', 'responsible']);
        });
    }

    public function update(AreaRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $area = $this->service->update($request->validated(), $id);
            return $area->load(['departments', 'responsible']);
        }, $id);
    }

    /**
     * Com a inversão da relação, um departamento pertence a no máximo uma área.
     * Mantém o endpoint devolvendo as áreas do departamento (0 ou 1).
     */
    public function byDepartment(int $departmentId)
    {
        try {
            $department = \App\Models\RH\Department\Department::with('area')->findOrFail($departmentId);

            return response()->json(
                $department->area ? [$department->area] : []
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao buscar áreas por departamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
