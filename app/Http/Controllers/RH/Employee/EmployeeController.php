<?php

namespace App\Http\Controllers\RH\Employee;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Employee\EmployeeRequest;
use App\Services\RH\Employee\EmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EmployeeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Funcionário';
    protected ?string $fieldName = 'full_name';

    public function __construct(EmployeeService $service)
    {
        $this->service = $service;
    }

    public function store(EmployeeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(EmployeeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function showPhoto(int $id)
    {
        try {
            $employee = $this->service->show($id);

            if (!$employee->photo_url) {
                return response()->json(['error' => 'Funcionário sem fotografia associada.'], Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($employee->getRawOriginal('photo_url'));

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Ficheiro não encontrado no servidor.'], Response::HTTP_NOT_FOUND);
            }

            $mimeType = mime_content_type($filePath);
            $fileName = basename($filePath);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Log::error('Erro ao abrir fotografia do funcionário', ['message' => $th->getMessage()]);
            return response()->json(['error' => 'Falha ao abrir o ficheiro.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
