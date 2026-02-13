<?php

namespace App\Http\Controllers\Alert\AlertUser;

use App\Http\Controllers\AbstractController;
use App\Services\Alert\AlertUser\AlertUserService;
use App\Http\Requests\Alert\AlertUser\AlertUserRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\returnSelf;

class AlertUserController extends AbstractController
{
    public function __construct(AlertUserService $service)
    {
        $this->service = $service;
    }


    public function findByUser(Request $request, $id)
    {
        try {
            if ($this->logRequest) {
                $this->logRequest();
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: "O utilizador " . Auth::user()->first_name . " Visualizou todos os registros no módulo {$this->nameEntity}",
                );
            }
    
            $filters = $request->get('filters') ?? $request->get('filtersV2');
        
    
            return $this->service->getUserWithAlerts($id);
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function getAllUsersAlertSummary(Request $request)
    {

        try {
            if ($this->logRequest) {
                $this->logRequest();
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: "O utilizador " . Auth::user()->first_name . " Visualizou todos os registros no módulo {$this->nameEntity}",
                );
            }

            $filters = $request['filters'] ?? $request['filtersV2'];
            $service = $this->service->index($request['paginate'], $filters, $request['orderBy'], $request['relationships']);

            return $users = $this->service->getAllUsersAlertSummary();
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function store(AlertUserRequest $request)
    {
        try {
            $this->logRequest();
            $alertUser = $this->service->storeMany($request->validated());
            return response()->json($alertUser, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countActiveAlertsForAuthenticatedUser()
    {
        try {
            $this->logRequest();
            $alertUser = $this->service->countActiveAlertsForAuthenticatedUser();
            return response()->json($alertUser, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(AlertUserRequest $request, $id)
    {
        try {
            $this->logRequest();
            $alertUser = $this->service->update($request->validated(), $id);
            return response()->json($alertUser, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
  

}
