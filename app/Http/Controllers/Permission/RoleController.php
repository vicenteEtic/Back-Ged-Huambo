<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\AbstractController;
use App\Services\Permission\RoleService;
use App\Http\Requests\Permission\RoleRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RoleController extends AbstractController
{
    protected ?string $logType = 'user';
    protected ?string $nameEntity = "Perfil";
    protected ?string $fieldName = "name";

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $role = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " criou o perfil {$role->name} com sucesso."
            );
            DB::commit();
            return response()->json($role, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "Erro ao criar perfil.",
            );
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro interno no servidor.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, $id)
    {
        Db::beginTransaction();
        try {
            $this->logRequest();
            $role = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: $this->logType,
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " atualizou o perfil {$role->name} com sucesso."
            );
            DB::commit();
            return response()->json($role, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou atualizar um perfil que não existe."
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            DB::rollBack();
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou atualizar o perfil {$id}, mas ocorreu um erro."
            );
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro interno no servidor.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
