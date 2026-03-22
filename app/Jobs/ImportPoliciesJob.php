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

    private int $chunkSize = 1000;

    public function handle()
    {
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

            $customersData = []; // acumulador global por cliente
            $rows = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row)) || str_contains($row[0], '---')) continue;

                $rows[] = $row;

                if (count($rows) >= $this->chunkSize) {
                    $this->processRows($rows, $header, $customersData);
                    $rows = [];
                }
            }

            if (!empty($rows)) {
                $this->processRows($rows, $header, $customersData);
            }

            fclose($handle);

            // Dispara jobs por cliente
            foreach ($customersData as $customerNumber => $policies) {
                $customer = Entities::where('customer_number', $customerNumber)->first();

                if (!$customer) {
                    Log::warning("Cliente não encontrado: {$customerNumber}");
                    continue;
                }

                ProcessCustomerPoliciesJob::dispatch($customer->id, $policies);
                Log::info("📬 Job KYT disparado para cliente {$customerNumber} com " . count($policies) . " apólices.");
            }

            Log::info("✅ CSV {$path} processado com sucesso");
        }
    }

    private function processRows(array $rows, array $header, array &$customersData): void
    {
        $idxCliente = array_search('NUMERO_CLIENTE', $header);
        $idxApolice = array_search('NUMERO_APOLICE', $header);
        $idxProduto = array_search('DESCRICAO_PRODUTO', $header);
        $idxEstado  = array_search('ESTADO_APOLICE', $header);
        $idxInicio  = array_search('DATA_INICIO', $header);
        $idxFim     = array_search('DATA_FIM', $header);
        $idxCapital = array_search('CAPITAL', $header);
        $idxPremio  = array_search('PREMIO_TOTAL', $header);
        $idxJuros   = array_search('JUROS', $header);

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

            $customersData[$customerNumber][] = $data;
        }
    }

    private function mapStatus(?string $value): string
    {
        $status = strtoupper(trim($value ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS', 'ANULADA', 'TERMINADA' => 'terminated',
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