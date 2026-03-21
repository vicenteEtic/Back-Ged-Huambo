<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\Entities\EntitiesService;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportClientesChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 5;
    public $timeout = 3600;
    public $backoff = 60;

    protected array $rows;
    protected array $header;

    public function __construct(array $rows, array $header)
    {
        $this->rows = $rows;
        $this->header = $header;
    }

    public function handle(EntitiesService $entityService)
    {
        $dataToUpsert = [];

        $idxCustomerNumber = array_search('NUMERO_CLIENTE', $this->header);
        $idxName = array_search('NOME_DENOMINACAO_SOCIAL', $this->header);
        $idxTipo = array_search('TIPO_CLIENTE', $this->header);
        $idxNif = array_search('NIF', $this->header);

        foreach ($this->rows as $row) {
            if (!isset($row[$idxCustomerNumber], $row[$idxName])) {
                Log::warning("Linha ignorada por falta de campos obrigatórios: " . implode(',', $row));
                continue;
            }

            $tipoCliente = strtoupper(trim($row[$idxTipo] ?? ''));
            $entityType = $tipoCliente === 'EMPRESA' ? 1 : 2;

            $dataToUpsert[] = [
                'customer_number' => (string)$row[$idxCustomerNumber],
                'social_denomination' => $row[$idxName],
                'policy_number' => null,
                'entity_type' => $entityType,
                'nif' => $row[$idxNif] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (!empty($dataToUpsert)) {
            try {
                Entities::upsert(
                    $dataToUpsert,
                    ['customer_number'], // chave única
                    ['social_denomination','entity_type','nif','updated_at'] // campos a atualizar
                );
                Log::info("Chunk processado com sucesso: " . count($dataToUpsert) . " registros.");
            } catch (\Exception $e) {
                Log::error("Falha ao processar chunk: " . $e->getMessage());
            }
        }
    }
}