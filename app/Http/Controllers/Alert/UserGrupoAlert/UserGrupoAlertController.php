<?php
    
    namespace App\Http\Controllers\Alert\UserGrupoAlert;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Alert\UserGrupoAlert\UserGrupoAlertService;
    use App\Http\Requests\Alert\UserGrupoAlert\UserGrupoAlertRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class UserGrupoAlertController extends AbstractController
    {
        public function __construct(UserGrupoAlertService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(UserGrupoAlertRequest $request)
        {
            try {
                $this->logRequest();
                $userGrupoAlert = $this->service->storeMany($request->validated());
               
        
                return response()->json($userGrupoAlert, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(UserGrupoAlertRequest $request, $id)
        {
            try {
                $this->logRequest();
                $userGrupoAlert = $this->service->update($request->validated(), $id);
                return response()->json($userGrupoAlert, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }