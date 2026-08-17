<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocument;
use App\Repositories\AbstractRepository;
use App\Services\Upload\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ArchiveDocumentRepository extends AbstractRepository
{
    public function __construct(
        ArchiveDocument $model,
        private FileUploadService $uploadService,
    ) {
        parent::__construct($model);
    }

    public function store(array $data): ArchiveDocument
    {
        try {
            return DB::transaction(function () use ($data) {
                $file = $data['file'] ?? null;
                unset($data['file']);

                if ($file instanceof UploadedFile) {
                    $employeeId = $data['employee_id'] ?? 'general';
                    $directory = $employeeId . '/archive-documents';

                    $result = $this->uploadService->processUploadedFile($file, $directory);

                    $data['file_path'] = 'storage/' . $result['path'];
                    $data['file_type'] = $this->uploadService->getUploadMimeType($file);
                    $data['file_size'] = $result['final_size'];
                    $data['mime_type'] = $result['mime_type'];
                }

                if (isset($data['metadata']) && is_string($data['metadata'])) {
                    $data['metadata'] = json_decode($data['metadata'], true);
                }
                if (isset($data['tags']) && is_string($data['tags'])) {
                    $data['tags'] = json_decode($data['tags'], true);
                }

                return $this->model->create($data);
            }, 6);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Lock wait timeout')) {
                throw new \Exception('O banco está ocupado, tente novamente em alguns segundos.');
            }
            throw $e;
        }
    }
}
