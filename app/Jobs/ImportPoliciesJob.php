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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 7200; // 2 horas
    public $tries = 3;

    private int $chunkSize = 1000; // menor que 10.000 para evitar placeholders

    public function handle()
    {
        $files = glob(base_path('Apolices_*.csv'));

        if (empty($files)) {
            Log::error("Nenhum CSV encontrado em base_path");
            return;
        }

        // Cria tabela persistente para cache, se não existir
        DB::statement('
            CREATE TABLE IF NOT EXISTS customer_policies_cache (
                customer_number VARCHAR(255) NOT NULL,
                numero_apolice VARCHAR(255) NOT NULL,
                descricao_produto VARCHAR(255),
                estado_apolice VARCHAR(50),
                data_inicio DATETIME,
                data_fim DATETIME,
                capital DOUBLE,
                premium_total DOUBLE,
                interest DOUBLE,
                PRIMARY KEY (customer_number, numero_apolice)
            )
        ');

        // Limpa cache antes de processar
        DB::table('customer_policies_cache')->truncate();

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

        // Agrupa por cliente usando SQL e dispara jobs
        $customers = DB::table('customer_policies_cache')
            ->select('customer_number', DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                "numero_apolice", numero_apolice,
                "descricao_produto", descricao_produto,
                "estado_apolice", estado_apolice,
                "data_inicio", data_inicio,
                "data_fim", data_fim,
                "capital", capital,
                "premium_total", premium_total,
                "interest", interest
            )) as policies'))
            ->groupBy('customer_number')
            ->get();

        foreach ($customers as $c) {
            $customer = Entities::where('customer_number', $c->customer_number)->first();
            if (!$customer) {
                Log::warning("Cliente não encontrado: {$c->customer_number}");
                continue;
            }

            $policies = json_decode($c->policies, true);
            ProcessCustomerPoliciesJob::dispatch($customer->id, $policies);
            Log::info("📬 Job KYT disparado para cliente {$c->customer_number} com " . count($policies) . " apólices.");
        }

        Log::info("✅ Todos os arquivos CSV processados e jobs KYT disparados");
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

        $insertData = [];

        foreach ($rows as $row) {
            $customerNumber = $row[$idxNumeroCliente] ?? null;
            $numeroApolice  = $row[$idxNumeroApolice] ?? null;
            if (!$customerNumber || !$numeroApolice) continue;

            $insertData[] = [
                'customer_number' => $customerNumber,
                'numero_apolice' => $numeroApolice,
                'descricao_produto' => strtoupper(trim($row[$idxDescricaoProduto] ?? '')),
                'estado_apolice' => $this->mapStatus($row[$idxEstado] ?? ''),
                'data_inicio' => $this->parseDate($row[$idxDataInicio] ?? null),
                'data_fim' => $this->parseDate($row[$idxDataFim] ?? null),
                'capital' => $this->toFloat($row[$idxCapital] ?? 0),
                'premium_total' => $this->toFloat($row[$idxPremioTotal] ?? 0),
                'interest' => $this->toFloat($row[$idxJuros] ?? 0),
            ];
        }

        // Divide insert em pedaços menores para evitar placeholders
        foreach (array_chunk($insertData, 500) as $chunk) {
            DB::table('customer_policies_cache')->insertOrIgnore($chunk);
        }
    }

    private function mapStatus(?string $value): string
    {
        $status = strtoupper(trim($value ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS','Anulada','Terminada' => 'terminated',
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