<?php

namespace App\Traits;

use App\Services\Upload\FileUploadService;
use Illuminate\Http\UploadedFile;

trait HasFileUploads
{
    private ?FileUploadService $fileUploadService = null;

    protected function getUploadService(): FileUploadService
    {
        if (!$this->fileUploadService) {
            $this->fileUploadService = app(FileUploadService::class);
        }
        return $this->fileUploadService;
    }

    protected function uploadAndCompress(
        UploadedFile $file,
        string $directory = 'uploads',
        ?string $disk = null,
    ): array {
        $service = $this->getUploadService();

        if ($disk) {
            $service = new FileUploadService($disk);
        }

        return $service->processUploadedFile($file, $directory);
    }

    protected function uploadMultipleAndCompress(
        array $files,
        string $directory = 'uploads',
        bool $createZip = false,
        ?string $disk = null,
    ): array {
        $service = $this->getUploadService();

        if ($disk) {
            $service = new FileUploadService($disk);
        }

        return $service->processMultipleFiles($files, $directory, $createZip);
    }

    protected function deleteUploadedFile(string $path, ?string $disk = null): bool
    {
        $service = $this->getUploadService();

        if ($disk) {
            $service = new FileUploadService($disk);
        }

        return $service->deleteFile($path);
    }

    protected function getUploadedFileInfo(string $path, ?string $disk = null): ?array
    {
        $service = $this->getUploadService();

        if ($disk) {
            $service = new FileUploadService($disk);
        }

        return $service->getFileInfo($path);
    }

    protected function getUploadMimeType(UploadedFile $file): string
    {
        $mime = $file->getClientMimeType();

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
