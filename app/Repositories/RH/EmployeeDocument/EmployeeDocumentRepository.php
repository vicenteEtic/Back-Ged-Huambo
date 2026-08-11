<?php

namespace App\Repositories\RH\EmployeeDocument;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Repositories\AbstractRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class EmployeeDocumentRepository extends AbstractRepository
{
    public function __construct(EmployeeDocument $model)
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
                $files  = $single ? [$files] : $files;

                if (!is_array($files)) {
                    throw new \Exception('Formato de ficheiros inválido.');
                }

                $documentType = null;

                if (! empty($data['document_type_id'])) {
                    $documentType = \App\Models\RH\EmployeeDocument\DocumentType::find($data['document_type_id']);
                }

                $created = [];

                foreach ($files as $file) {
                    $path = $file->store('' . $data['employee_id'] . '/employee-documents', 'public');

                    $created[] = $this->model->create([
                        'employee_id'      => $data['employee_id'],
                        'document_type_id' => $data['document_type_id'] ?? null,
                        'document_type'    => $documentType?->name ?? $data['document_type'] ?? $this->guessDocumentType($file),
                        'name'             => $data['name'] ?? $file->getClientOriginalName(),
                        'description'      => $data['description'] ?? null,
                        'file_path'        => 'storage/'.$path,
                        'expiry_date'      => $data['expiry_date'] ?? null,
                        'issue_date'       => $data['issue_date'] ?? null,
                        'place_of_issue'   => $data['place_of_issue'] ?? null,
                        'is_verified'      => $data['is_verified'] ?? false,
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

    public function update(array $data, int $id)
    {
        if (! empty($data['document_type_id'])) {
            $type = \App\Models\RH\EmployeeDocument\DocumentType::find($data['document_type_id']);
            if ($type) {
                $data['document_type'] = $type->name;
            }
        }

        return parent::update($data, $id);
    }

    public function findBy(array $criteria)
    {
        return $this->model->query()
            ->with(['employee', 'documentType'])
            ->where($criteria)
            ->get();
    }

    protected function guessDocumentType(UploadedFile $file): string
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
