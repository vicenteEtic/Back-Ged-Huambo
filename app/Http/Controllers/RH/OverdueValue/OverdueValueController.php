<?php

namespace App\Http\Controllers\RH\OverdueValue;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\OverdueValue\OverdueValueRequest;
use App\Services\RH\OverdueValue\OverdueValueService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OverdueValueController extends AbstractController
{
    protected ?string $logType = 'rh';

    protected ?string $nameEntity = 'Valor em Atraso';

    protected ?string $fieldName = 'id';

    public function __construct(OverdueValueService $service)
    {
        $this->service = $service;
    }

    public function store(OverdueValueRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            return $this->service->store($request->validated());
        });
    }

    public function update(OverdueValueRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function summary(Request $request)
    {
        try {
            $employeeId = $request->integer('employee_id') ?: null;
            $type = $request->string('type')->toString() ?: null;
            $status = $request->string('status')->toString() ?: null;

            return response()->json($this->service->summary($employeeId, $type, $status));
        } catch (\Exception $e) {
            Log::error('Erro ao obter resumo de valores em atraso', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
