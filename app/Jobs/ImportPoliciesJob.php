<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Jobs\ProcessCustomerPoliciesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 10800; // 3 horas
    public $tries = 5;

    private int $chunkSize = 1000; // linhas por chunk
    private array $allCustomersData = []; // armazena todas as apólices

    public function handle()
    {
        try {
            $files = glob(base_path('Apolices_*.csv'));

            if (empty($files)) {
                Log::error("Nenhum CSV encontrado em base_path");
                return;
            }

            foreach ($files as $path) {
                if (!file_exists($path)) continue;

                if (($handle = fopen($path, 'r')) === false) {
                    Log::error("Erro ao abrir CSV: {$path}");
                    continue;
                }

                Log::info("📄 Processando CSV: {$path}");
                $header = fgetcsv($handle, 0, ',');
                $header = array_map(fn($h) => strtoupper(trim($h)), $header);

                $rows = [];
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    if (empty(array_filter($row)) || str_contains($row[0], '---')) continue;

                    $rows[] = $row;

                    if (count($rows) >= $this->chunkSize) {
                        $this->processChunk($rows, $header);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    $this->processChunk($rows, $header);
                }

                fclose($handle);
            }

            // Dispara o job de KYT para cada cliente com todas as apólices acumuladas
            foreach ($this->allCustomersData as $customerNumber => $policies) {
                $customer = Entities::where('customer_number', $customerNumber)->first();

                if (!$customer) {
                    Log::warning("Cliente não encontrado: {$customerNumber}");
                    continue;
                }

                ProcessCustomerPoliciesJob::dispatch($customer->id, $policies);
                Log::info("📬 Job KYT disparado para cliente {$customerNumber} com " . count($policies) . " apólices.");
            }

            Log::info("✅ Todos os arquivos CSV processados e jobs KYT disparados");

        } catch (\Throwable $e) {
            Log::error("Erro no ImportPoliciesJob: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
        }
    }

    private function processChunk(array $rows, array $header): void
    {
        $idxNumeroCliente = array_search('NUMERO_CLIENTE', $header);
        $idxNumeroApolice = array_search('NUMERO_APOLICE', $header);
        $idxDescricaoProduto = array_search('DESCRICAO_PRODUTO', $header);
        $idxEstado = array_search('ESTADO_APOLICE', $header);
        $idxDataInicio = array_search('DATA_INICIO', $header);
        $idxDataFim = array_search('DATA_FIM', $header);
        $idxCapital = array_search('CAPITAL', $header);
        $idxPremioTotal = array_search('PREMIO_TOTAL', $header);
        $idxJuros = array_search('JUROS', $header);

        foreach ($rows as $row) {
            $customerNumber = $row[$idxNumeroCliente] ?? null;
            $numeroApolice  = $row[$idxNumeroApolice] ?? null;

            if (!$customerNumber || !$numeroApolice) continue;

            $data = [
                'numero_cliente' => $customerNumber,
                'numero_apolice' => $numeroApolice,
                'descricao_produto' => strtoupper(trim($row[$idxDescricaoProduto] ?? '')),
                'estado_apolice' => $this->mapStatus($row[$idxEstado] ?? ''),
                'data_inicio' => $this->parseDate($row[$idxDataInicio] ?? null),
                'data_fim' => $this->parseDate($row[$idxDataFim] ?? null),
                'capital' => $this->toFloat($row[$idxCapital] ?? 0),
                'premium_total' => $this->toFloat($row[$idxPremioTotal] ?? 0),
                'interest' => $this->toFloat($row[$idxJuros] ?? 0),
            ];

            // concatena novas apólices se cliente já tiver registros anteriores
            $this->allCustomersData[$customerNumber][] = $data;
        }
    }

    private function mapStatus(?string $value): string
    {
        $status = strtoupper(trim($value ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS', 'Anulada', 'Terminada' => 'terminated',
            default => 'unknown',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        $date = substr(trim($date), 0, 19);
        return $date ?: null;
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }
}