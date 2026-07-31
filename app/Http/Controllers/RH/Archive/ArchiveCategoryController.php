<?php

namespace App\Http\Controllers\RH\Archive;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Archive\ArchiveCategoryRequest;
use App\Services\RH\Archive\ArchiveCategoryService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ArchiveCategoryController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Categoria de Arquivo';
    protected ?string $fieldName = 'id';

    public function __construct(ArchiveCategoryService $service)
    {
        $this->service = $service;
    }

    public function store(ArchiveCategoryRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $model = $this->service->store($data);
            return $model->load(['parent', 'creator']);
        });
    }

    public function update(ArchiveCategoryRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $model = $this->service->update($request->validated(), $id);
            return $model->load(['parent', 'creator']);
        }, $id);
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
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
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
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
