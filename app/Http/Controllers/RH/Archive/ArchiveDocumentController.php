<?php

namespace App\Http\Controllers\RH\Archive;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Archive\ArchiveDocumentRequest;
use App\Http\Requests\RH\Archive\ArchiveDocumentShareRequest;
use App\Http\Requests\RH\Archive\ArchiveDocumentVersionRequest;
use App\Models\RH\Archive\ArchiveDocument;
use App\Models\RH\Archive\ArchiveDocumentShare;
use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Services\RH\Archive\ArchiveDocumentService;
use App\Services\RH\Archive\ArchiveDocumentShareService;
use App\Services\RH\Archive\ArchiveDocumentVersionService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ArchiveDocumentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Documento de Arquivo';
    protected ?string $fieldName = 'id';

    public function __construct(
        ArchiveDocumentService $service,
        protected ArchiveDocumentVersionService $versionService,
        protected ArchiveDocumentShareService $shareService,
    ) {
        $this->service = $service;
    }

    public function store(ArchiveDocumentRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $model = $this->service->store($data);
            return $model->load(['category', 'employee', 'creator']);
        });
    }

    public function update(ArchiveDocumentRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $data = $request->validated();
            if (isset($data['metadata'])) {
                $data['metadata'] = is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata'];
            }
            if (isset($data['tags'])) {
                $data['tags'] = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
            }
            $model = $this->service->update($data, $id);
            return $model->load(['category', 'employee', 'creator']);
        }, $id);
    }

    public function byEmployee(int $employeeId)
    {
        try {
            $documents = ArchiveDocument::where('employee_id', $employeeId)
                ->with(['category', 'creator'])
                ->orderByDesc('created_at')
                ->paginate(request('paginate', 50));
            return response()->json($documents);
        } catch (Exception $e) {
            Log::error('Erro ao buscar documentos do funcionário', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function byCategory(int $categoryId)
    {
        try {
            $documents = ArchiveDocument::where('category_id', $categoryId)
                ->with(['employee', 'creator'])
                ->orderByDesc('created_at')
                ->paginate(request('paginate', 50));
            return response()->json($documents);
        } catch (Exception $e) {
            Log::error('Erro ao buscar documentos por categoria', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function approve(int $id)
    {
        try {
            DB::beginTransaction();
            $document = ArchiveDocument::findOrFail($id);
            $document->update([
                'status' => 'published',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            DB::commit();
            return response()->json($document->load(['category', 'approver']));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao aprovar documento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function archive(int $id)
    {
        try {
            DB::beginTransaction();
            $document = ArchiveDocument::findOrFail($id);
            $document->update(['status' => 'archived']);
            DB::commit();
            return response()->json($document);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao arquivar documento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function search()
    {
        try {
            $query = ArchiveDocument::with(['category', 'employee', 'creator']);

            if ($q = request('q')) {
                $query->where(function ($qry) use ($q) {
                    $qry->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('document_number', 'like', "%{$q}%")
                        ->orWhere('reference_number', 'like', "%{$q}%");
                });
            }

            if ($type = request('type')) {
                $query->whereHas('category', fn($q) => $q->where('type', $type));
            }

            if ($status = request('status')) {
                $query->where('status', $status);
            }

            if ($confidentiality = request('confidentiality')) {
                $query->where('confidentiality', $confidentiality);
            }

            if ($employeeId = request('employee_id')) {
                $query->where('employee_id', $employeeId);
            }

            if ($categoryId = request('category_id')) {
                $query->where('category_id', $categoryId);
            }

            if ($dateFrom = request('issued_date_from')) {
                $query->whereDate('issued_date', '>=', $dateFrom);
            }

            if ($dateTo = request('issued_date_to')) {
                $query->whereDate('issued_date', '<=', $dateTo);
            }

            return response()->json($query->orderByDesc('created_at')->paginate(request('paginate', 50)));
        } catch (Exception $e) {
            Log::error('Erro ao pesquisar documentos', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function showFile(int $id)
    {
        try {
            $document = ArchiveDocument::findOrFail($id);

            if (!$document->file_path) {
                return response()->json(['error' => 'Documento sem ficheiro associado.'], Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($document->file_path);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Ficheiro não encontrado no servidor.'], Response::HTTP_NOT_FOUND);
            }

            $mimeType = File::mimeType($filePath);
            $fileName = basename($filePath);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Log::error('Erro ao abrir ficheiro do arquivo', ['message' => $th->getMessage()]);
            return response()->json(['error' => 'Falha ao abrir o ficheiro.'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function showVersionFile(int $versionId)
    {
        try {
            $version = ArchiveDocumentVersion::findOrFail($versionId);

            if (!$version->file_path) {
                return response()->json(['error' => 'Versão sem ficheiro associado.'], Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($version->file_path);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Ficheiro não encontrado no servidor.'], Response::HTTP_NOT_FOUND);
            }

            $mimeType = File::mimeType($filePath);
            $fileName = basename($filePath);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Log::error('Erro ao abrir ficheiro de versão', ['message' => $th->getMessage()]);
            return response()->json(['error' => 'Falha ao abrir o ficheiro.'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function versions(int $documentId)
    {
        try {
            $versions = ArchiveDocumentVersion::where('archive_document_id', $documentId)
                ->with('creator')
                ->orderByDesc('version_number')
                ->get();
            return response()->json($versions);
        } catch (Exception $e) {
            Log::error('Erro ao buscar versões', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function storeVersion(ArchiveDocumentVersionRequest $request, int $documentId)
    {
        try {
            DB::beginTransaction();
            $this->logRequest();
            ArchiveDocument::findOrFail($documentId);
            $data = $request->validated();
            $data['archive_document_id'] = $documentId;
            $data['created_by'] = auth()->id();
            $model = $this->versionService->store($data);
            DB::commit();
            return response()->json($model->load('creator'), Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar versão', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function shares(int $documentId)
    {
        try {
            $shares = ArchiveDocumentShare::where('archive_document_id', $documentId)
                ->with(['sharedWithUser', 'sharedWithEmployee', 'sharer'])
                ->get();
            return response()->json($shares);
        } catch (Exception $e) {
            Log::error('Erro ao buscar partilhas', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function storeShare(ArchiveDocumentShareRequest $request, int $documentId)
    {
        try {
            DB::beginTransaction();
            $this->logRequest();
            ArchiveDocument::findOrFail($documentId);
            $data = $request->validated();
            $data['archive_document_id'] = $documentId;
            $data['shared_by'] = auth()->id();
            $model = $this->shareService->store($data);
            DB::commit();
            return response()->json($model->load(['sharedWithUser', 'sharedWithEmployee', 'sharer']), Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar partilha', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroyShare(int $documentId, int $shareId)
    {
        try {
            DB::beginTransaction();
            $this->logRequest();
            $share = ArchiveDocumentShare::where('archive_document_id', $documentId)->findOrFail($shareId);
            $share->delete();
            DB::commit();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao eliminar partilha', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
