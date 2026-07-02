<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\InterviewRequest;
use App\Services\RH\Recruitment\InterviewService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterviewController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Interview';
    protected ?string $fieldName = 'id';

    public function __construct(InterviewService $service)
    {
        $this->service = $service;
    }

    public function store(InterviewRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $interview = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Interview scheduled by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($interview, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating interview', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(InterviewRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $interview = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Interview updated by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($interview, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating interview', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
