<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\Process\ProcessDocumentRequest;
use App\Services\Process\ProcessDocumentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDocumentController extends AbstractController
{
    protected ?string $logType = 'process';
    protected ?string $nameEntity = 'ProcessDocument';
    protected ?string $fieldName = 'name';

    public function __construct(ProcessDocumentService $service)
    {
        $this->service = $service;
    }

    public function store(ProcessDocumentRequest $request, int $processId)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['process_id'] = $processId;
            $documents = $this->service->store($data);
            $count = is_array($documents) ? count($documents) : 1;
            $this->logToDatabase(
                type: 'process',
                level: 'info',
                customMessage: $count . ' documento(s) adicionado(s) ao processo por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($documents, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating process document', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byProcess(int $processId)
    {
        try {
            $this->logRequest();
            $documents = $this->service->byProcess($processId);
            return response()->json($documents);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Error fetching process documents', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $processId, int $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $this->service->destroy($id);
            $this->logToDatabase(
                type: 'process',
                level: 'info',
                customMessage: 'Documento do processo removido por ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error deleting process document', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
