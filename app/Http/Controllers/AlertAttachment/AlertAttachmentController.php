<?php

namespace App\Http\Controllers\AlertAttachment;

use App\Http\Controllers\AbstractController;
use App\Services\AlertAttachment\AlertAttachmentService;
use App\Http\Requests\AlertAttachment\AlertAttachmentRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class AlertAttachmentController extends AbstractController
{
    public function __construct(AlertAttachmentService $service)
    {
        $this->service = $service;
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      

    /**
     * Store a newly created resource in storage.
     */
    public function store(AlertAttachmentRequest $request, $alertID)
    {
        try {
            $this->logRequest();
            $alertAttachment = $this->service->createComplaintAttachment($request->validated(), $alertID);
            return response()->json($alertAttachment, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AlertAttachmentRequest $request, $id)
    {
        try {
            $this->logRequest();
            $alertAttachment = $this->service->update($request->validated(), $id);
            return response()->json($alertAttachment, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showFile($id)
    {

        try {
            $this->logRequest();
            $complaint = $this->service->showFile($id);
            return response()->json($complaint, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
