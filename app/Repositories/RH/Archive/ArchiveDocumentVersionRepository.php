<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Repositories\AbstractRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ArchiveDocumentVersionRepository extends AbstractRepository
{
    public function __construct(ArchiveDocumentVersion $model)
    {
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
                    $path = $file->store($archiveDocumentId . '/archive-document-versions', 'public');

                    $data['file_path'] = $path;
                    $data['file_size'] = $file->getSize();
                    $data['mime_type'] = $file->getMimeType();
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
