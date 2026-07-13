<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessDocument;
use App\Repositories\AbstractRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProcessDocumentRepository extends AbstractRepository
{
    public function __construct(ProcessDocument $model)
    {
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

                foreach ($files as $file) {
                    $path = $file->store($data['process_id'] . '/process-documents', 'public');

                    $created[] = $this->model->create([
                        'process_id' => $data['process_id'],
                        'document_type' => $data['document_type'],
                        'name' => $data['name'] ?? $file->getClientOriginalName(),
                        'description' => $data['description'] ?? null,
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
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
