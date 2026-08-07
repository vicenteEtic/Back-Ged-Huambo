<?php

namespace App\Http\Controllers\RH\Declaration;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Declaration\DeclarationRequestForm;
use App\Services\RH\Declaration\DeclarationRequestService;
use DomainException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeclarationRequestController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Solicitação de Declaração';
    protected ?string $fieldName = 'id';

    public function __construct(DeclarationRequestService $service)
    {
        $this->service = $service;
    }

    public function store(DeclarationRequestForm $request)
    {
        return $this->handleStore(function () use ($request) {
            $declaration = $this->service->submit($request->validated());
            return $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Solicitação de declaração ' . $declaration->reference_number . ' submetida por ' . auth()->user()->first_name
            ) ? $declaration : $declaration;
        });
    }

    public function update(DeclarationRequestForm $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function preview(Request $request)
    {
        try {
            $validated = $request->validate([
                'declaration_type_id' => 'required|integer|exists:declaration_types,id',
                'employee_id' => 'required|integer|exists:employees,id',
            ]);

            return response()->json($this->service->preview($validated['declaration_type_id'], $validated['employee_id']));
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao gerar pré-visualização de declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function previewRequest(int $id)
    {
        try {
            return response()->json($this->service->previewRequest($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao gerar pré-visualização de declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approve(Request $request, int $id)
    {
        try {
            $request->validate(['comment' => 'nullable|string']);
            $model = $this->service->approve($id, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao aprovar solicitação de declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject(Request $request, int $id)
    {
        try {
            $request->validate(['reason' => 'required|string']);
            $model = $this->service->reject($id, auth()->id(), $request->reason);
            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao rejeitar solicitação de declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function issue(int $id)
    {
        try {
            $model = $this->service->issue($id, auth()->id());
            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao emitir declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pending()
    {
        try {
            return response()->json($this->service->pending());
        } catch (Exception $e) {
            Log::error('Erro ao listar solicitações pendentes de declaração', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
