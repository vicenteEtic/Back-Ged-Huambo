<?php

namespace App\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Imagick;

class FileUploadService
{
    private string $disk;

    private array $config = [
        'pdf' => [
            'quality' => 'ebook',
            'dpi' => 150,
            'image_quality' => 80,
        ],
        'image' => [
            'jpeg_quality' => 80,
            'png_quality' => 8,
            'webp_quality' => 80,
            'max_width' => 2000,
            'max_height' => 2000,
        ],
        'zip' => [
            'level' => 6,
        ],
    ];

    private array $compressStats = [];

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?? config('upload.default_disk', 'public');
        $this->config['pdf'] = config('upload.pdf', $this->config['pdf']);
        $this->config['image'] = config('upload.image', $this->config['image']);
        $this->config['zip'] = config('upload.zip', $this->config['zip']);
    }

    public function processUploadedFile(
        UploadedFile $file,
        string $directory = '',
        ?callable $onProgress = null,
    ): array {
        $originalSize = $file->getSize();
        $mimeType = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileName = $this->generateFileName($file);

        $processedPath = match (true) {
            str_contains($mimeType, 'pdf') => $this->compressPdf($file, $directory, $fileName),
            str_contains($mimeType, 'image') => $this->compressImage($file, $directory, $fileName, $mimeType),
            default => $this->storeOriginal($file, $directory, $fileName),
        };

        $finalSize = Storage::disk($this->disk)->size($processedPath);
        $reduction = $originalSize > 0 ? round((1 - $finalSize / $originalSize) * 100, 1) : 0;

        $this->logCompression($file->getClientOriginalName(), $originalSize, $finalSize, $reduction, $mimeType);

        return [
            'path' => $processedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => Storage::disk($this->disk)->mimeType($processedPath),
            'extension' => $extension,
            'original_size' => $originalSize,
            'final_size' => $finalSize,
            'reduction_percent' => $reduction,
            'url' => Storage::disk($this->disk)->url($processedPath),
        ];
    }

    public function processMultipleFiles(
        array $files,
        string $directory = '',
        bool $createZip = false,
    ): array {
        $results = [];
        $this->compressStats = ['original' => 0, 'final' => 0];

        foreach ($files as $file) {
            $result = $this->processUploadedFile($file, $directory);
            $results[] = $result;
            $this->compressStats['original'] += $result['original_size'];
            $this->compressStats['final'] += $result['final_size'];
        }

        if ($createZip && count($results) > 1) {
            $zipResult = $this->createZipFromUploadedFiles($files, $directory);
            $results['zip'] = $zipResult;
        }

        $totalReduction = $this->compressStats['original'] > 0
            ? round((1 - $this->compressStats['final'] / $this->compressStats['original']) * 100, 1)
            : 0;

        return [
            'files' => $results,
            'stats' => [
                'total_original' => $this->compressStats['original'],
                'total_final' => $this->compressStats['final'],
                'total_reduction_percent' => $totalReduction,
                'count' => count($files),
            ],
        ];
    }

    public function compressPdf(UploadedFile $file, string $directory, string $fileName): string
    {
        $tempInput = tempnam(sys_get_temp_dir(), 'upload_pdf_in_');
        $tempOutput = tempnam(sys_get_temp_dir(), 'upload_pdf_out_');

        try {
            $file->move(dirname($tempInput), basename($tempInput));

            if ($this->isGhostscriptAvailable()) {
                $result = $this->runGhostscript($tempInput, $tempOutput);

                if ($result && file_exists($tempOutput) && filesize($tempOutput) > 0) {
                    $compressed = file_get_contents($tempOutput);
                    $original = file_get_contents($tempInput);

                    if (strlen($compressed) < strlen($original)) {
                        $destPath = $directory ? "{$directory}/{$fileName}" : $fileName;
                        Storage::disk($this->disk)->put($destPath, $compressed);
                        return $destPath;
                    }
                }
            }

            $destPath = $directory ? "{$directory}/{$fileName}" : $fileName;
            $file->storeAs($directory, $fileName, $this->disk);
            return $destPath;
        } finally {
            @unlink($tempInput);
            @unlink($tempOutput);
        }
    }

    private function runGhostscript(string $inputPath, string $outputPath): bool
    {
        $quality = $this->config['pdf']['quality'];
        $dpi = $this->config['pdf']['dpi'];
        $imageQuality = $this->config['pdf']['image_quality'];

        $command = sprintf(
            'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s '
            . '-dNOPAUSE -dQUIET -dBATCH '
            . '-dColorImageResolution=%d '
            . '-dGrayImageResolution=%d '
            . '-dMonoImageResolution=%d '
            . '-dDownsampleColorImages=true '
            . '-dDownsampleGrayImages=true '
            . '-dColorImageDownsampleThreshold=1.0 '
            . '-dGrayImageDownsampleThreshold=1.0 '
            . '-dJPEGQFactor=%d '
            . '-sOutputFile=%s %s 2>&1',
            escapeshellarg($quality),
            $dpi,
            $dpi,
            $dpi,
            $imageQuality,
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    public function compressImage(
        UploadedFile $file,
        string $directory,
        string $fileName,
        string $mimeType,
    ): string {
        $tempInput = tempnam(sys_get_temp_dir(), 'upload_img_in_');

        try {
            $file->move(dirname($tempInput), basename($tempInput));

            if ($this->isImagickAvailable()) {
                $compressed = $this->compressWithImagick($tempInput, $mimeType);

                if ($compressed !== false) {
                    $destPath = $directory ? "{$directory}/{$fileName}" : $fileName;
                    Storage::disk($this->disk)->put($destPath, $compressed);
                    return $destPath;
                }
            }

            $file->storeAs($directory, $fileName, $this->disk);
            return $directory ? "{$directory}/{$fileName}" : $fileName;
        } finally {
            @unlink($tempInput);
        }
    }

    private function compressWithImagick(string $tempPath, string $mimeType): string|false
    {
        try {
            $imagick = new Imagick();
            $imagick->readImage($tempPath);

            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            $maxWidth = $this->config['image']['max_width'];
            $maxHeight = $this->config['image']['max_height'];

            if ($width > $maxWidth || $height > $maxHeight) {
                $imagick->resizeImage(
                    $maxWidth,
                    $maxHeight,
                    Imagick::FILTER_LANCZOS,
                    1,
                    true,
                );
            }

            $imagick->stripImage();

            return match (true) {
                str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') => $this->outputJpeg($imagick),
                str_contains($mimeType, 'png') => $this->outputPng($imagick),
                str_contains($mimeType, 'webp') => $this->outputWebp($imagick),
                default => $this->outputJpeg($imagick),
            };
        } catch (\Exception) {
            return false;
        }
    }

    private function outputJpeg(Imagick $imagick): string
    {
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality($this->config['image']['jpeg_quality']);
        $imagick->setOption('jpeg:dct-method', 'float');
        return $imagick->getImageBlob();
    }

    private function outputPng(Imagick $imagick): string
    {
        $imagick->setImageFormat('png');
        $imagick->setImageCompressionQuality($this->config['image']['png_quality']);
        $imagick->setOption('png:compression-filter', '5');
        $imagick->setOption('png:compression-level', '9');
        $imagick->setOption('png:compression-strategy', '1');
        return $imagick->getImageBlob();
    }

    private function outputWebp(Imagick $imagick): string
    {
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality($this->config['image']['webp_quality']);
        return $imagick->getImageBlob();
    }

    public function storeOriginal(UploadedFile $file, string $directory, string $fileName): string
    {
        $file->storeAs($directory, $fileName, $this->disk);
        return $directory ? "{$directory}/{$fileName}" : $fileName;
    }

    public function createZipFromFiles(array $filePaths, string $zipName, string $directory = ''): array
    {
        $zip = new ZipArchive();
        $tempZip = tempnam(sys_get_temp_dir(), 'upload_zip_');

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o ficheiro ZIP.');
        }

        foreach ($filePaths as $path) {
            $fullPath = Storage::disk($this->disk)->path($path);
            if (file_exists($fullPath)) {
                $archiveName = basename($path);
                $zip->addFile($fullPath, $archiveName);
            }
        }

        $zip->setCompressionIndex(0, ZipArchive::CM_DEFAULT);
        $zip->close();

        $destPath = $directory ? "{$directory}/{$zipName}" : $zipName;
        Storage::disk($this->disk)->put($destPath, file_get_contents($tempZip));
        @unlink($tempZip);

        $finalSize = Storage::disk($this->disk)->size($destPath);

        return [
            'path' => $destPath,
            'original_name' => $zipName,
            'mime_type' => 'application/zip',
            'extension' => 'zip',
            'original_size' => $finalSize,
            'final_size' => $finalSize,
            'reduction_percent' => 0,
            'url' => Storage::disk($this->disk)->url($destPath),
        ];
    }

    public function createZipFromUploadedFiles(array $files, string $directory = ''): array
    {
        $tempFiles = [];
        $zip = new ZipArchive();
        $tempZip = tempnam(sys_get_temp_dir(), 'upload_zip_');

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o ficheiro ZIP.');
        }

        foreach ($files as $file) {
            $tempPath = tempnam(sys_get_temp_dir(), 'upload_zip_content_');
            $file->moveUsingStream($tempPath);
            $tempFiles[] = $tempPath;
            $zip->addFile($tempPath, $file->getClientOriginalName());
        }

        $zip->close();

        $zipName = 'files_' . Str::random(8) . '.zip';
        $destPath = $directory ? "{$directory}/{$zipName}" : $zipName;
        Storage::disk($this->disk)->put($destPath, file_get_contents($tempZip));

        foreach ($tempFiles as $tp) {
            @unlink($tp);
        }
        @unlink($tempZip);

        $finalSize = Storage::disk($this->disk)->size($destPath);

        return [
            'path' => $destPath,
            'original_name' => $zipName,
            'mime_type' => 'application/zip',
            'extension' => 'zip',
            'original_size' => $finalSize,
            'final_size' => $finalSize,
            'reduction_percent' => 0,
            'url' => Storage::disk($this->disk)->url($destPath),
        ];
    }

    public function deleteFile(string $path): bool
    {
        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }
        return false;
    }

    public function getFileInfo(string $path): ?array
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'size' => Storage::disk($this->disk)->size($path),
            'mime_type' => Storage::disk($this->disk)->mimeType($path),
            'last_modified' => Storage::disk($this->disk)->lastModified($path),
            'url' => Storage::disk($this->disk)->url($path),
        ];
    }

    private function generateFileName(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    private function isGhostscriptAvailable(): bool
    {
        exec('which gs', $output, $returnCode);
        return $returnCode === 0;
    }

    private function isImagickAvailable(): bool
    {
        return class_exists(Imagick::class);
    }

    public function setConfig(array $config): self
    {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getCompressStats(): array
    {
        return $this->compressStats;
    }

    private function logCompression(string $fileName, int $originalSize, int $finalSize, float $reduction, string $mimeType): void
    {
        $original = $this->formatBytes($originalSize);
        $final = $this->formatBytes($finalSize);
        $saved = $this->formatBytes($originalSize - $finalSize);

        if ($reduction > 0) {
            Log::info("[Upload] Compressão aplicada: {$fileName}", [
                'tipo' => $mimeType,
                'tamanho_original' => $original,
                'tamanho_final' => $final,
                'economia' => $saved,
                'reducao' => "{$reduction}%",
            ]);
        } else {
            Log::info("[Upload] Ficheiro armazenado sem compressão: {$fileName}", [
                'tipo' => $mimeType,
                'tamanho' => $original,
            ]);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
