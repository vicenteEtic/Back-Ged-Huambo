<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocument;
use App\Repositories\AbstractRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ArchiveDocumentRepository extends AbstractRepository
{
    public function __construct(ArchiveDocument $model)
    {
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
                    $path = $file->store($employeeId . '/archive-documents', 'public');

                    $data['file_path'] = 'storage/'.$path;
                    $data['file_type'] = $this->guessFileType($file);
                    $data['file_size'] = $file->getSize();
                    $data['mime_type'] = $file->getMimeType();
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

    protected function guessFileType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        return match (true) {
            str_contains($mime, 'pdf') => 'PDF',
            str_contains($mime, 'image') => 'Imagem',
            str_contains($mime, 'word') || str_contains($mime, 'document') => 'Documento Word',
            str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') => 'Folha de cálculo',
            str_contains($mime, 'text') => 'Texto',
            default => $mime,
        };
    }
}
