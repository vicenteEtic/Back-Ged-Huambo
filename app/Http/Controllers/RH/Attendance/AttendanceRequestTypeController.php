<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AttendanceRequestTypeRequest;
use App\Services\RH\Attendance\AttendanceRequestTypeService;
use DomainException;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AttendanceRequestTypeController extends AbstractController
{
    protected ?string $logType = 'rh';

    protected ?string $nameEntity = 'Tipo de Solicitação de Dispensa';

    protected ?string $fieldName = 'name';

    public function __construct(AttendanceRequestTypeService $service)
    {
        $this->service = $service;
    }

    public function store(AttendanceRequestTypeRequest $request)
    {
        return $this->handleStore(
            fn () => $this->service->store($request->validated()),
        );
    }

    public function update(AttendanceRequestTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error("Erro ao remover {$this->nameEntity} {$id}", ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
