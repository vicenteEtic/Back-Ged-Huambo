<?php

namespace App\Http\Controllers\Alert;

use App\Services\Alert\AlertService;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\Alert\AlertDocumentRequest;
use App\Http\Requests\Alert\AlertUpdateStatusRequest;
use App\Services\Alert\AlertUser\AlertUserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class AlertController extends AbstractController
{
    protected ?string $logType = 'alert';
    protected ?string $nameEntity = "Alerta";
    protected ?string $fieldName = "name";
       private $alertUserService;
    public function __construct(AlertService $service, AlertUserService $alertUserService)
    {
        $this->service = $service;
              $this->alertUserService = $alertUserService;
    }
    public function getTotalAlerts()
    {
        try {


            return $this->service->getTotalAlerts();
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




    public function status(AlertUpdateStatusRequest $request, $id)
    {

            $this->logRequest();
            $statusOlder =$request->validated()['is_active'];

            if ($statusOlder == 1) {
                $status = "Novo";
            } elseif ($statusOlder == 2) {
                $status = "Em tratamento";
            } elseif ($statusOlder == 3) {
                $status = "Em Supervisão";
            } elseif ($statusOlder == 0) {
                $status = "Fechado";
            } else {
                $status = "Desconhecido";
            }
            
            $commentAlert = $this->service->updateStatus($request->validated(), $id);
 $alert=["is_read"=>   $statusOlder];
            $this->alertUserService->updateAlertUser( $alert,$id);

            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                alert_id: $id,
           customMessage: "Estado do alerta ID #{$id} alterado para '{$status}'"


            );
            return response()->json($commentAlert, Response::HTTP_OK);
            try {    } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function document(AlertDocumentRequest $request)
    {
        try {
            $this->logRequest();
            $commentAlert = $this->service->document($request->validated());
            return response()->json($commentAlert, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
