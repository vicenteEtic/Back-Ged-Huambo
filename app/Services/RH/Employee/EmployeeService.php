<?php

namespace App\Services\RH\Employee;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Repositories\RH\Employee\EmployeeRepository;
use App\Services\AbstractService;
use App\Services\Upload\FileUploadService;
use Illuminate\Http\UploadedFile;

class EmployeeService extends AbstractService
{
    public function __construct(
        EmployeeRepository $repository,
        private FileUploadService $uploadService,
    ) {
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

        $directory = $employee->id . '/photos';
        $result = $this->uploadService->processUploadedFile($photo, $directory);
        $employee->photo_url = 'storage/' . $result['path'];
        $employee->save();
    }

    protected function storeDocuments(int $employeeId, array $documents): void
    {
        foreach ($documents as $doc) {
            $file = $doc['file_path'] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $directory = $employeeId . '/employee-documents';
            $result = $this->uploadService->processUploadedFile($file, $directory);

            EmployeeDocument::create([
                'employee_id' => $employeeId,
                'document_type' => $doc['document_type'] ?? $file->getMimeType(),
                'name' => $doc['document_type'] ?? $file->getClientOriginalName(),
                'description' => $doc['description'] ?? null,
                'file_path' => $result['path'],
                'expiry_date' => $doc['expiry_date'] ?? null,
            ]);
        }
    }
}
