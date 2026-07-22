<?php

namespace App\Http\Controllers\RH\EmployeeDocument;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\EmployeeDocument\EmployeeDocumentRequest;
use App\Services\RH\EmployeeDocument\EmployeeDocumentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Documento do Funcionário';
    protected ?string $fieldName = 'name';

    public function __construct(EmployeeDocumentService $service)
    {
        parent::__construct($service);
    }

    public function findBy(int|string $id = 0)
    {
        $employeeId = request()->route('employee_id');
        $documents = $this->service->findBy(['employee_id' => $employeeId]);
        return response()->json($documents);
    }

    public function store(EmployeeDocumentRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            return $this->service->store($data);
        });
    }

    public function update(EmployeeDocumentRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function destroy($id)
    {
        try {
            $document = $this->service->show($id);
            $this->service->destroy($id);

            $this->logToDatabase(
                type: $this->logType,
                level: 'info',
                customMessage: "Documento {$id} removido do funcionário {$document->employee_id} por " . auth()->user()->first_name
            );

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao apagar documento do funcionário', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function showFile(int $id)
    {
        try {
            $document = $this->service->show($id);

            if (!$document->file_path) {
                return response()->json(['error' => 'Documento sem ficheiro associado.'], Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($document->file_path);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Ficheiro não encontrado no servidor.'], Response::HTTP_NOT_FOUND);
            }

            $mimeType = File::mimeType($filePath);
            $fileName = basename($filePath);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Log::error('Erro ao abrir ficheiro de documento', ['message' => $th->getMessage()]);
            return response()->json(['error' => 'Falha ao abrir o ficheiro.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
