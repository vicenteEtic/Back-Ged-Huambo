<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\UploadRequest;
use App\Services\Upload\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function __construct(
        private FileUploadService $uploadService,
    ) {}

    public function store(UploadRequest $request): JsonResponse
    {
        try {
            $directory = $request->input('directory', 'uploads');
            $createZip = $request->boolean('create_zip', false);

            $files = $request->file('files');

            if (count($files) === 1) {
                $result = $this->uploadService->processUploadedFile($files[0], $directory);
                return response()->json([
                    'message' => 'Ficheiro carregado com sucesso.',
                    'data' => $result,
                ], Response::HTTP_CREATED);
            }

            $result = $this->uploadService->processMultipleFiles($files, $directory, $createZip);

            return response()->json([
                'message' => count($files) . ' ficheiros carregados com sucesso.',
                'data' => $result,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Erro ao carregar ficheiro', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'Erro ao carregar ficheiro: ' . $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function compress(array $filePaths): JsonResponse
    {
        try {
            $results = [];

            foreach ($filePaths as $path) {
                $mimeType = mime_content_file(storage_path('app/public/' . $path));
                if (str_contains($mimeType, 'pdf')) {
                    $tempIn = tempnam(sys_get_temp_dir(), 'compress_in');
                    $tempOut = tempnam(sys_get_temp_dir(), 'compress_out');
                    file_put_contents($tempIn, file_get_contents(storage_path('app/public/' . $path)));
                    exec("gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$tempOut} {$tempIn}", $out, $code);
                    if ($code === 0 && filesize($tempOut) < filesize($tempIn)) {
                        Storage::disk('public')->put($path, file_get_contents($tempOut));
                    }
                    @unlink($tempIn);
                    @unlink($tempOut);
                }
            }

            return response()->json(['message' => 'Compressão concluída.', 'data' => $results]);
        } catch (Exception $e) {
            Log::error('Erro ao comprimir ficheiro', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy(string $path): JsonResponse
    {
        try {
            $deleted = $this->uploadService->deleteFile($path);

            if (!$deleted) {
                return response()->json(['error' => 'Ficheiro não encontrado.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            Log::error('Erro ao eliminar ficheiro', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function info(string $path): JsonResponse
    {
        try {
            $info = $this->uploadService->getFileInfo($path);

            if (!$info) {
                return response()->json(['error' => 'Ficheiro não encontrado.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => $info]);
        } catch (Exception $e) {
            Log::error('Erro ao obter informações do ficheiro', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
