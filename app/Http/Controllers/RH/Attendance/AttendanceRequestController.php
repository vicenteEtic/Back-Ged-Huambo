<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Requests\RH\Attendance\AttendanceRequestFormRequest;
use App\Services\RH\Attendance\AttendanceRequestService;
use DomainException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AttendanceRequestController
{
    public function __construct(
        protected AttendanceRequestService $service
    ) {}

    public function metadata()
    {
        try {
            return response()->json([
                'types' => $this->service->types(),
                'statuses' => $this->service->statuses(),
                'document_labels' => $this->service->documentLabels(),
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao carregar metadados de solicitações de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'employee_id', 'date', 'start_date', 'end_date']);
            $paginate = $request->input('paginate');

            return response()->json($this->service->index($paginate, $filters));
        } catch (Exception $e) {
            Log::error('Erro ao listar solicitações de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id)
    {
        try {
            return response()->json($this->service->showWithRelations($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao visualizar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(AttendanceRequestFormRequest $request)
    {
        try {
            $data = $request->validated();
            $files = array_values($data['documents'] ?? []);
            unset($data['documents']);

            $model = $this->service->create($data, $files, auth()->id());

            return response()->json($model, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao criar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(AttendanceRequestFormRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $files = array_values($data['documents'] ?? []);
            unset($data['documents']);

            $model = $this->service->update($data, $id, $files, auth()->id());

            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao actualizar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id, auth()->id());

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao eliminar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function underReview(int $id)
    {
        try {
            $model = $this->service->markUnderReview($id, auth()->id());

            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao marcar solicitação em análise', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approve(Request $request, int $id)
    {
        try {
            $request->validate(['note' => 'nullable|string|max:2000']);
            $model = $this->service->approve($id, auth()->id(), $request->input('note'));

            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao aprovar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject(Request $request, int $id)
    {
        try {
            $request->validate(['note' => 'required|string|max:2000']);
            $model = $this->service->reject($id, $request->input('note'), auth()->id());

            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao rejeitar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $request->validate(['note' => 'nullable|string|max:2000']);
            $model = $this->service->cancel($id, auth()->id(), $request->input('note'));

            return response()->json($model);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erro de validação.', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao cancelar solicitação de dispensa', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function despacho(int $id)
    {
        try {
            return response()->json($this->service->despatchedFile($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao gerar despacho', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadDespacho(int $id)
    {
        try {
            return $this->service->downloadDespacho($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao descarregar despacho', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadDocument(int $id, int $documentId)
    {
        try {
            return $this->service->downloadDocument($id, $documentId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            Log::error('Erro ao descarregar documento da solicitação', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
