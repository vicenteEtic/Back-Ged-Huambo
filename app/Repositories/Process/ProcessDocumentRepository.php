<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessDocument;
use App\Repositories\AbstractRepository;
use App\Services\Upload\FileUploadService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProcessDocumentRepository extends AbstractRepository
{
    public function __construct(
        ProcessDocument $model,
        private FileUploadService $uploadService,
    ) {
        parent::__construct($model);
    }

    public function store(array $data): mixed
    {
        try {
            return DB::transaction(function () use ($data) {
                $files = $data['file_path'] ?? [];

                if (empty($files)) {
                    throw new \Exception('Nenhum ficheiro enviado.');
                }

                $single = $files instanceof UploadedFile;
                $files = $single ? [$files] : $files;

                if (!is_array($files)) {
                    throw new \Exception('Formato de ficheiros inválido.');
                }

                $created = [];
                $directory = $data['process_id'] . '/process-documents';

                foreach ($files as $file) {
                    $result = $this->uploadService->processUploadedFile($file, $directory);

                    $created[] = $this->model->create([
                        'process_id' => $data['process_id'],
                        'document_type' => $data['document_type'] ?? $this->uploadService->getUploadMimeType($file),
                        'name' => $data['name'] ?? $file->getClientOriginalName(),
                        'description' => $data['description'] ?? null,
                        'file_path' => $result['path'],
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $result['final_size'],
                        'mime_type' => $result['mime_type'],
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                return $single ? $created[0] : $created;
            }, 6);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Lock wait timeout')) {
                throw new \Exception('O banco está ocupado, tente novamente em alguns segundos.');
            }
            throw $e;
        }
    }

    public function byProcess(int $processId)
    {
        return $this->model->where('process_id', $processId)
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();
    }
}
