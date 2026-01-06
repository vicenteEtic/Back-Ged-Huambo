<?php

namespace App\Repositories\AlertAttachment;

use App\Models\AlertAttachment\AlertAttachment;
use App\Repositories\AbstractRepository;
use Throwable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlertAttachmentRepository extends AbstractRepository
{
    public function __construct(AlertAttachment $model)
    {
        parent::__construct($model);
    }

    public function createComplaintAttachment(array $attachments, int $alertID): array
    {
        $attachmentsCreated = [];
    
        Log::debug("📎 Iniciando upload de anexos para Alerta #{$alertID}", [
            'total' => count($attachments)
        ]);
    
        foreach ($attachments as $index => $file) {
            try {
                // Verifica se é um UploadedFile
                if (!is_a($file, \Illuminate\Http\UploadedFile::class)) {
                    Log::warning("❌ Anexo não é um arquivo válido", ['index' => $index]);
                    continue;
                }
    
                Log::debug("🔍 Processando anexo {$index}", [
                    'original_name' => $file->getClientOriginalName(),
                    'size_kb' => round($file->getSize() / 1024, 2)
                ]);
    
                // Salvar no storage público
                $path = $file->store("alert/{$alertID}", 'public');
    
                // Criar registro no banco
                $created = $this->model->create([
                    'alert_id' => $alertID,
                    'file'     => $path,
                    'name'     => $file->getClientOriginalName(),
                ]);
    
                $attachmentsCreated[] = $created;
    
                Log::info("💾 Anexo cadastrado no banco", ['id' => $created->id, 'path' => $path]);
            } catch (\Throwable $e) {
                Log::error("🔥 Erro ao salvar anexo da Alerta {$alertID}", [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    
        return $attachmentsCreated;
    }
    
    
    



    public function files($alert_id)
    {
        try {
            $response = $this->model::where('alert_id', $alert_id)->get();
          
           // $this->CraftHistory->log('info', 'Visualizou ficheiros da solicitação com o código ' .  $code, Auth::user()->fullName, Auth::user()->id, null, 'user', null);
            $data = [];
            foreach ($response as $attachment) {
                $filePath = storage_path("app/public/" . $attachment->path);
                if (file_exists($filePath)) {
                    $fileSize = filesize($filePath);
                    $fileType = mime_content_type($filePath);
                    $data[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => $fileSize,
                        'type' => $fileType,
                    ];
                } else {
                    $data[] = [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => 0,
                        'type' => 'unknown',
                        'message' => 'Arquivo não encontrado.',
                    ];
                }
            }

            if (empty($data)) {
                return response()->json([
                    "message" => "Nenhum anexo encontrado."
                ], 404);
            }

            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => "Erro ao listar arquivos",
                "error" => $th->getMessage()
            ], 400);
        }
    }
    public function showFile($id)
    {
        $file = $this->model::findOrFail($id);
    
        if (!$file->file || !Storage::disk('public')->exists($file->file)) {
            throw new \Exception('Arquivo não encontrado.');
        }
    
        // Retorna o caminho absoluto para o controller
        return Storage::disk('public')->path($file->file);
    }
    
    
}
