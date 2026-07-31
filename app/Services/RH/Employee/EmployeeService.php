<?php

namespace App\Services\RH\Employee;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Repositories\RH\Employee\EmployeeRepository;
use App\Services\AbstractService;
use Illuminate\Http\UploadedFile;

class EmployeeService extends AbstractService
{
    public function __construct(EmployeeRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        $documents = $data['documents'] ?? [];
        unset($data['documents']);

        $photo = $data['photo_url'] ?? null;
        unset($data['photo_url']);

        $data = $this->clean($data);
        $employee = $this->repository->store($data);

        $this->storePhoto($employee, $photo);
        $this->storeDocuments($employee->id, $documents);

        return $employee->fresh(['documents']);
    }

    public function update(array $data, int $id)
    {
        $documents = $data['documents'] ?? [];
        unset($data['documents']);

        $photo = $data['photo_url'] ?? null;
        unset($data['photo_url']);

        $data = $this->clean($data);
        $employee = $this->repository->update($data, $id);

        $this->storePhoto($employee, $photo);

        if (!empty($documents)) {
            $this->storeDocuments($employee->id, $documents);
        }

        return $employee->fresh(['documents']);
    }

    protected function storePhoto($employee, $photo): void
    {
        if (!$photo instanceof UploadedFile) {
            return;
        }

        $path = $photo->store('employees/photos', 'public');
        $employee->photo_url = $path;
        $employee->save();
    }

    protected function storeDocuments(int $employeeId, array $documents): void
    {
        foreach ($documents as $doc) {
            $file = $doc['file_path'] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store($employeeId . '/employee-documents', 'public');

            EmployeeDocument::create([
                'employee_id' => $employeeId,
                'document_type' => $doc['document_type'] ?? $this->guessDocumentType($file),
                'name' => $doc['name'] ?? $file->getClientOriginalName(),
                'description' => $doc['description'] ?? null,
                'file_path' => $path,
                'expiry_date' => $doc['expiry_date'] ?? null,
            ]);
        }
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
