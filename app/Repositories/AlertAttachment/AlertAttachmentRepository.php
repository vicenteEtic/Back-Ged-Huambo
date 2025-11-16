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

        Log::debug("📎 Iniciando upload de anexos para ALerta #{$alertID}", [
            'total' => count($attachments)
        ]);

        foreach ($attachments as $index => $base64File) {
            try {
                Log::debug("🔍 Processando anexo {$index}");

              

                // garantir que está no formato "data:xxx;base64,yyyy"
                if (!preg_match('/^data:(.*?);base64,(.*)$/', $base64File, $matches)) {
                    Log::warning("❌ String não corresponde ao padrão Base64 esperado", [
                        'index' => $index,
                        'preview' => substr($base64File, 0, 50)
                    ]);
                    continue;
                }

                $mimeType = $matches[1] ?? 'application/octet-stream';
                $fileData = base64_decode($matches[2], true);

                if ($fileData === false) {
                    throw new \Exception("Falha ao decodificar Base64");
                }

                Log::debug("✅ Base64 decodificado com sucesso", [
                    'mimeType' => $mimeType,
                    'size'     => strlen($fileData)
                ]);

                // descobrir extensão
                $extension = explode('/', $mimeType)[1] ?? 'bin';
                $randomName = $this->model::generateCustomRandomCode(12) . '.' . $extension;
                $path = "alert/{$alertID}/{$randomName}";

                // salvar no disco
                Storage::disk('public')->put($path, $fileData);

                Log::debug("📂 Arquivo salvo no storage", ['path' => $path]);

                // registrar no banco
                $created = $this->model->create([
                    'alert_id' => $alertID,
                    'file'         => $path,
                    'name'         => "dn_{$randomName}",
                ]);

                Log::info("💾 Anexo cadastrado no banco", ['id' => $created->id]);

                $attachmentsCreated[] = $created;
            } catch (Throwable $e) {
                Log::error("🔥 Erro ao salvar anexo da ALerta {$alertID}", [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $attachmentsCreated;
    }


    public function showFile($id)
    {
        $file = $this->model::findOrFail($id);

        $currentDomain = request()->getSchemeAndHttpHost();

        if (Str::contains($currentDomain, 'nossa-denuncias.keepcomply.co.ao')) {
            $baseUrl = 'https://nossa-denuncias.keepcomply.co.ao:1130/';
        } else {
            $baseUrl = 'http://172.17.100.11:1129';
        }
        $url = "{$baseUrl}/storage/{$file->file}";

        return response()->json([
            'id'  => $file->id,
            'url' => $url,
            'domain_detected' => $currentDomain, // opcional, para debug
        ]);
    }
}
