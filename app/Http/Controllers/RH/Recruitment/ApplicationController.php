<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\ApplicationRequest;
use App\Services\RH\Recruitment\ApplicationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Application';
    protected ?string $fieldName = 'id';

    public function __construct(ApplicationService $service)
    {
        $this->service = $service;
    }

    public function store(ApplicationRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $application = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Application #' . $application->id . ' created by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($application, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating application', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ApplicationRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $application = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Application #' . $application->id . ' updated by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($application, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating application', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
