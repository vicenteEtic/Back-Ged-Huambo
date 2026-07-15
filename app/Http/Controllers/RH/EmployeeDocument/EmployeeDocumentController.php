<?php

namespace App\Http\Controllers\RH\EmployeeDocument;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\EmployeeDocument\EmployeeDocumentRequest;
use App\Services\RH\EmployeeDocument\EmployeeDocumentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'EmployeeDocument';
    protected ?string $fieldName = 'name';

    public function __construct(EmployeeDocumentService $service)
    {
        $this->service = $service;
    }

   

    public function store(EmployeeDocumentRequest $request, int $employeeId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['employee_id'] = $employeeId;
            $documents = $this->service->store($data);
            $count = is_array($documents) ? count($documents) : 1;
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: $count . ' document(s) created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($documents, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating employee document', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EmployeeDocumentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $document = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Document ' . $document->name . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($document, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating employee document', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
