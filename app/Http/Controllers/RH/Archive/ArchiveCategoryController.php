<?php

namespace App\Http\Controllers\RH\Archive;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Archive\ArchiveCategoryRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveCategoryController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'ArchiveCategory';
    protected ?string $fieldName = 'id';

    public function __construct(\App\Services\RH\Archive\ArchiveCategoryService $service)
    {
        $this->service = $service;
    }

    public function store(ArchiveCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $model = $this->service->store($data);
            DB::commit();
            return response()->json($model->load(['parent', 'creator']), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar categoria de arquivo', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ArchiveCategoryRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($model->load(['parent', 'creator']), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar categoria de arquivo', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function tree()
    {
        try {
            $categories = \App\Models\RH\Archive\ArchiveCategory::with(['children' => function ($q) {
                $q->orderBy('sort_order')->where('is_active', true);
            }])
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            return response()->json($categories);
        } catch (Exception $e) {
            Log::error('Erro ao buscar árvore de categorias', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byType(string $type)
    {
        try {
            $categories = \App\Models\RH\Archive\ArchiveCategory::where('type', $type)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            return response()->json($categories);
        } catch (Exception $e) {
            Log::error('Erro ao buscar categorias por tipo', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
