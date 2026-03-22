<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Jobs\ProcessCustomerPoliciesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 3 horas
    public $tries = 5;

    private int $chunkSize = 1000; // número de linhas por batch

    // Armazena todas as apólices por cliente
    private array $allCustomersData = [];

    public function handle()
    {
        try {
            $filePath = base_path('Apolices_Vida.csv');

            if (!file_exists($filePath)) {
                Log::error("Nenhum CSV encontrado em: {$filePath}");
                return;
            }

            if (($handle = fopen($filePath, 'r')) === false) {
                Log::error("Erro ao abrir CSV: {$filePath}");
                return;
            }

            Log::info("📄 Iniciando processamento CSV: {$filePath}");

            $header = fgetcsv($handle, 0, ',');
            $header = array_map(fn($h) => strtoupper(trim($h)), $header);

            $rows = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {

                if (empty(array_filter($row))) continue;
                if (!is_numeric($row[0]) || !is_numeric($row[1])) continue;

                $rows[] = $row;

                if (count($rows) >= $this->chunkSize) {
                    $this->accumulateRows($rows, $header);
                    $rows = [];
                }
            }

            if (!empty($rows)) {
                $this->accumulateRows($rows, $header);
            }

            fclose($handle);

            // Agora dispara **apenas um job por cliente**, com todas as apólices acumuladas
            foreach ($this->allCustomersData as $customerNumber => $policies) {
                $customer = Entities::where('customer_number', $customerNumber)->first();
                if (!$customer) {
                    Log::warning("Cliente não encontrado: {$customerNumber}");
                    continue;
                }

                ProcessCustomerPoliciesJob::dispatch($customer->id, $policies)
                    ->onQueue('policies');

                Log::info("📬 Job final disparado para cliente {$customerNumber} com " . count($policies) . " apólices.");
            }

            Log::info("✅ CSV processado com sucesso, todos os jobs disparados.");

        } catch (\Throwable $e) {
            Log::error("Erro ao processar CSV: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Acumula apólices de cada chunk no array global
     */
    private function accumulateRows(array $rows, array $header): void
    {
        $idxCliente  = array_search('NUMERO_CLIENTE', $header);
        $idxApolice  = array_search('NUMERO_APOLICE', $header);
        $idxProduto  = array_search('DESCRICAO_PRODUTO', $header);
        $idxEstado   = array_search('ESTADO_APOLICE', $header);
        $idxInicio   = array_search('DATA_INICIO', $header);
        $idxFim      = array_search('DATA_FIM', $header);
        $idxCapital  = array_search('CAPITAL', $header);
        $idxPremio   = array_search('PREMIO_TOTAL', $header);
        $idxJuros    = array_search('JUROS', $header);

        foreach ($rows as $row) {
            $customerNumber = $row[$idxCliente] ?? null;
            $numeroApolice  = $row[$idxApolice] ?? null;

            if (!$customerNumber || !$numeroApolice) continue;

            $data = [
                'numero_cliente'    => $customerNumber,
                'numero_apolice'    => $numeroApolice,
                'descricao_produto' => strtoupper(trim($row[$idxProduto] ?? '')),
                'estado_apolice'    => $this->mapStatus($row[$idxEstado] ?? ''),
                'data_inicio'       => $this->parseDate($row[$idxInicio] ?? null),
                'data_fim'          => $this->parseDate($row[$idxFim] ?? null),
                'capital'           => $this->toFloat($row[$idxCapital] ?? 0),
                'premium_total'     => $this->toFloat($row[$idxPremio] ?? 0),
                'interest'          => $this->toFloat($row[$idxJuros] ?? 0),
            ];

            // Acumula todas as apólices do cliente
            $this->allCustomersData[$customerNumber][] = $data;
        }
    }

    private function mapStatus(?string $value): string
    {
        $status = strtoupper(trim($value ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS' => 'terminated',
            default => 'unknown',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        return substr(trim($date), 0, 19) ?: null;
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }
}