<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;

class ImportEntitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;

    public function __construct()
    {
        $this->filePath = base_path("NOSSA - Dados de Apólices.csv");
    }

    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            Log::error("ImportEntitiesJob: Arquivo não encontrado: {$this->filePath}");
            return;
        }

        if (($handle = fopen($this->filePath, 'r')) === false) {
            Log::error("ImportEntitiesJob: Não foi possível abrir o arquivo: {$this->filePath}");
            return;
        }

        $imported = 0;
        $header = null;

        while (($row = fgetcsv($handle, 0, ";")) !== false) { // <-- separador ; 

            // Remove espaços e caracteres invisíveis
            $row = array_map(function ($cell) {
                $cell = trim($cell);
                $cell = preg_replace('/[\x00-\x1F\x7F]/u', '', $cell);
                return $cell;
            }, $row);

            // Pula linhas totalmente vazias
            if (count(array_filter($row)) === 0) {
                continue;
            }

            // Detecta headers
            if (!$header) {
                $header = array_map(function ($h) {
                    $h = trim($h);
                    $h = preg_replace('/^\x{FEFF}/u', '', $h); // remove BOM no primeiro header
                    return $h;
                }, $row);

                Log::info("Headers detectados: " . implode(", ", $header));
                continue;
            }

            // Combina row com header
            $data = @array_combine($header, $row);
            if (!$data) {
                Log::warning("Falha ao combinar linha com headers: " . json_encode($row));
                continue;
            }

            // Ignora entidades sem nome
            if (empty($data['Denominação Social'])) {
                Log::warning("Ignorado — entidade sem nome: " . json_encode($data));
                continue;
            }

            try {
                Entities::updateOrCreate(
                    ['customer_number' => $data['Número de Cliente'] ?? null],
                    [
                        'social_denomination' => $data['Denominação Social'] ?? null,
                        'policy_number'       => $data['Número da Apólice'] ?? null,
                        'entity_type'         => 1,
                        'nif'                 => $data['NIF'] ?? null,
                    ]
                );

                $imported++;

                if ($imported % 100 === 0) {
                    Log::info("ImportEntitiesJob: {$imported} registros importados...");
                }
            } catch (\Throwable $e) {
                Log::warning("Erro ao importar registro: {$e->getMessage()} — Dados: " . json_encode($data));
            }
        }

        fclose($handle);
        Log::info("ImportEntitiesJob finalizado: {$imported} registros importados com sucesso.");
    }
}
