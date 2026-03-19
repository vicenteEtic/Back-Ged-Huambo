<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\Entities\EntitiesService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportClientesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = 60;

    protected EntitiesService $entityService;

    public function handle(EntitiesService $entityService)
    {
        $this->entityService = $entityService;
        $path = base_path('clientes.csv');

        if (!file_exists($path)) {
            Log::error("Arquivo CSV não encontrado: {$path}");
            return;
        }

        $totalInserted = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ',');
            $header = array_map('trim', $header);

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row))) continue;

                $row = array_map(fn($v) => $v === 'NULL' ? null : $v, $row); // converte "NULL" para null

                $data = array_combine($header, $row);

                if (empty($data['NUMERO_CLIENTE']) || empty($data['NOME_DENOMINACAO_SOCIAL'])) {
                    Log::warning("Linha ignorada por falta de campos obrigatórios: " . implode(',', $row));
                    continue;
                }

                $tipoCliente = strtoupper(trim($data['TIPO_CLIENTE'] ?? ''));
                $entityType = $tipoCliente === 'EMPRESA' ? 1 : 2; // 1=Empresa, 2=Individual

                $dataImport = [
                    'customer_number' => (string)($data['NUMERO_CLIENTE']),
                    'social_denomination' => $data['NOME_DENOMINACAO_SOCIAL'],
                    'nif' => $data['NIF'] ?? null,
                    'entity_type' => $entityType,
                   
                ];

                try {
                    $this->entityService->storeOrUpdate(
                        ['customer_number' => $dataImport['customer_number']],
                        $dataImport
                    );

                    $totalInserted++;

                    if ($totalInserted % 1000 === 0) gc_collect_cycles();
                } catch (\Exception $e) {
                    Log::error("Falha ao inserir cliente {$dataImport['customer_number']}: " . $e->getMessage());
                }
            }

            fclose($handle);
        }

        Log::info("Importação de clientes concluída! Total inserido/atualizado: {$totalInserted}");
    }
}