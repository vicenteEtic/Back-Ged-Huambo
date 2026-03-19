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

    public $tries = 3;        // número de tentativas
    public $timeout = 3600;   // 1 hora, aumenta se necessário
    public $backoff = 60;     // espera 1 minuto antes de re-tentar

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

            $idxCustomerNumber = array_search('NUMERO_CLIENTE', $header);
            $idxName = array_search('NOME_DENOMINACAO_SOCIAL', $header);
            $idxTipo = array_search('TIPO_CLIENTE', $header);
            $idxNif = array_search('NIF', $header);

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row))) continue;

                if (!isset($row[$idxCustomerNumber], $row[$idxName])) {
                    Log::warning("Linha ignorada por falta de campos obrigatórios: " . implode(',', $row));
                    continue;
                }


                $tipoCliente = strtoupper(trim($row[$idxTipo] ?? '')); // CORRIGIDO
                $entityType = $tipoCliente === 'EMPRESA' ? 1 : 2;

                $dataImport = [
                    "social_denomination" => $row[$idxName] ?? null,
                    "customer_number" => (string)($row[$idxCustomerNumber] ?? null),
                    "policy_number" => null,
                    "entity_type" => $entityType,
                    "nif" => $row[$idxNif] ?? null,
                ];

                try {
                    $this->entityService->storeOrUpdate(
                        ['customer_number' => $dataImport['customer_number']],
                        $dataImport
                    );

                    $totalInserted++;

                    // Limpeza periódica de memória para evitar estouro
                    if ($totalInserted % 1000 === 0) {
                        gc_collect_cycles();
                    }
                } catch (\Exception $e) {
                    Log::error("Falha ao inserir cliente {$dataImport['customer_number']}: " . $e->getMessage());
                }
            }

            fclose($handle);
        }

        Log::info("Importação de clientes concluída! Total inserido/atualizado: {$totalInserted}");
    }
}
