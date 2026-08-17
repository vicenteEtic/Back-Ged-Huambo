<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Repositories\AbstractRepository;
use App\Services\Upload\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ArchiveDocumentVersionRepository extends AbstractRepository
{
    public function __construct(
        ArchiveDocumentVersion $model,
        private FileUploadService $uploadService,
    ) {
        parent::__construct($model);
    }

    public function store(array $data): ArchiveDocumentVersion
    {
        try {
            return DB::transaction(function () use ($data) {
                $file = $data['file'] ?? null;
                unset($data['file']);

                if ($file instanceof UploadedFile) {
                    $archiveDocumentId = $data['archive_document_id'];
                    $directory = $archiveDocumentId . '/archive-document-versions';

                    $result = $this->uploadService->processUploadedFile($file, $directory);

                    $data['file_path'] = $result['path'];
                    $data['file_size'] = $result['final_size'];
                    $data['mime_type'] = $result['mime_type'];
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
