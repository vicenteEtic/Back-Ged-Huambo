<?php

namespace App\Http\Controllers\Alert\GrupoType;

use App\Http\Controllers\AbstractController;
use App\Services\Alert\GrupoType\GrupoTypeService;
use App\Http\Requests\Alert\GrupoType\GrupoTypeRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class GrupoTypeController extends AbstractController
{
    public function __construct(GrupoTypeService $service)
    {
        $this->service = $service;
    }

    public function listTypGrup()
    {
        try {
            $this->logRequest();
            $grupoType = $this->service->listTypGrup();
            return response()->json($grupoType, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function store(GrupoTypeRequest $request)
    {
        try {
            $this->logRequest();
            $grupoType = $this->service->store($request->validated());
            return response()->json($grupoType, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GrupoTypeRequest $request, $id)
    {
        try {
            $this->logRequest();
            $grupoType = $this->service->update($request->validated(), $id);
            return response()->json($grupoType, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
