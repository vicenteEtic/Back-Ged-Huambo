<?php

namespace App\Http\Controllers\User;

use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\User\UserService;
use App\Http\Requests\User\AuthRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\Verify2faRequest;
use App\Traits\DatabaseLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Monolog\Level;

class UserController extends AbstractController
{
    protected ?string $logType = 'user';
    protected ?string $nameEntity = "Usuário";
    protected ?string $fieldName = "first_name";

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function login(AuthRequest $request)
    {
     
            $this->logRequest();
            $token = $this->service->login($request);
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: 'Iniciou sessão.',
                
                
            );
            return response()->json(['api_token' => $token], Response::HTTP_OK);
            try {
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase('error', 'Erro ao iniciar sessão do usuário.');
            return response()->json($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }
    public function logout(Request $request)
    {
        try {
            $this->logRequest();
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: 'Terminou sessão.',
            );
            $response = $this->service->logout($request);
            return response()->json(["message" => "Sessão terminada!"], Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Erro ao terminar sessão do usuário.',
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
    public function store(UserRequest $request)
    {
        try {
            $this->logRequest();
            $user = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "Usuário {$user?->first_name} criado com sucesso.",
            );
            return response()->json($user, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Erro ao criar usuário.',
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
    public function update(UserRequest $request, $id)
    {
        try {
            $this->logRequest();
            $user = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "Usuário {$user?->first_name} atualizado com sucesso.",
            );
            return response()->json($user, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Usuário não encontrado.',
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Erro ao atualizar usuário.',
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

     public function changePassword(ChangePasswordRequest $request,$id)
    {
        try {
            $this->logRequest();
            $user = $this->service->changePassword($request->validated(),$id);
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "Usuário {$user?->first_name} editou sua senha com sucesso.",
            );
            return response()->json($user, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Usuário não encontrado.',
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: 'Erro ao atualizar usuário.',
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
    
    public function me()
    {
        try {
            $this->logRequest();
            $user = $this->service->me();
            return response()->json($user, Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logRequest($e);
             Log::error('Erro interno', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Erro interno no servidor.'
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function verify2fa(Verify2faRequest $request)
    {
        try {
          
            $user = $this->service->verify2fa($request->validated());
            return response()->json($user, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
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
