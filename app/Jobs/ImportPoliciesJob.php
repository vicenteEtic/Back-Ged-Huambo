<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Jobs\ProcessCustomerPoliciesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 3h
    public $tries = 3;
    private int $chunkSize = 100; // tamanho de inserção no banco
    private int $jobChunkSize = 500; // tamanho de apólices por job de processamento

    public function handle()
    {
        try {
            // Busca todos os arquivos Apolices_*.csv
            $files = glob(base_path('Apolices_*.csv'));
            if (!$files) {
                Log::warning("Nenhum arquivo Apolices_*.csv encontrado");
                return;
            }

            foreach ($files as $filePath) {
                if (!file_exists($filePath)) continue;

                Log::info("📄 Iniciando importação do arquivo: {$filePath}");
                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    Log::error("Erro ao abrir CSV: {$filePath}");
                    continue;
                }

                $header = null;
                $rows = [];
                $inserted = 0;

                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    $line = array_map('trim', $line);
                    if (empty(array_filter($line))) continue;
                    if (str_contains(implode(',', $line), '---')) continue;

                    // Define header
                    if (!$header) {
                        $header = array_map(fn($h) => strtoupper($h), $line);
                        continue;
                    }

                    $row = $this->mapRow($line, $header);
                    if (!$row['numero_cliente'] || !$row['numero_apolice']) continue;

                    $rows[] = $row;

                    // Inserção em batch no staging
                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policies_staging')->insert($rows);
                        $inserted += count($rows);
                        Log::info("📦 Inseridos {$inserted} registros do arquivo {$filePath}");
                        $rows = [];
                        gc_collect_cycles();
                    }
                }

                // Último batch
                if (!empty($rows)) {
                    DB::table('policies_staging')->insert($rows);
                    $inserted += count($rows);
                    $rows = [];
                }

                fclose($handle);
                Log::info("✅ Finalizado arquivo: {$filePath}, total inserido: {$inserted}");

                // Dispara jobs por cliente após inserir o arquivo
                $this->dispatchJobsByCustomer();
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro na importação: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function dispatchJobsByCustomer()
    {
        Log::info("🚀 Disparando jobs por cliente...");

        DB::table('policies_staging')
            ->orderBy('numero_cliente')
            ->chunk(500, function ($rows) {

                // Agrupa por cliente
                $grouped = $rows->groupBy('numero_cliente');

                foreach ($grouped as $numero_cliente => $policies) {

                    // Busca entidade
                    $entity = Entities::where('customer_number', $numero_cliente)->first();
                    if (!$entity) {
                        Log::warning("Cliente não encontrado: {$numero_cliente}");
                        continue;
                    }

                    // Converte Collection para array simples
                    $policiesArray = $policies->map(function ($row) {
                        return [
                            'numero_apolice'    => $row->numero_apolice,
                            'descricao_produto' => $row->descricao_produto,
                            'estado_apolice'    => $row->estado_apolice,
                            'data_inicio'       => $row->data_inicio,
                            'data_fim'          => $row->data_fim,
                            'capital'           => $row->capital,
                            'premium_total'     => $row->premium_total,
                            'interest'          => $row->interest,
                        ];
                    })->toArray();

                    // Quebra em chunks menores por job, evita sobrecarregar memória
                    foreach (array_chunk($policiesArray, $this->jobChunkSize) as $chunk) {
                        ProcessCustomerPoliciesJob::dispatch($entity->id, $chunk)
                            ->onQueue('high');
                    }

                    Log::info("📬 Jobs disparados para cliente {$numero_cliente} com " . count($policiesArray) . " apólices.");
                }

                unset($rows, $grouped, $policiesArray);
                gc_collect_cycles();
            });

        Log::info("✅ Todos os jobs disparados com sucesso.");
    }

    private function mapRow(array $row, array $header): array
    {
        return [
            'numero_cliente'    => $row[array_search('NUMERO_CLIENTE', $header)] ?? null,
            'numero_apolice'    => $row[array_search('NUMERO_APOLICE', $header)] ?? null,
            'descricao_produto' => $row[array_search('DESCRICAO_PRODUTO', $header)] ?? null,
            'estado_apolice'    => $this->mapStatus($row[array_search('ESTADO_APOLICE', $header)] ?? null),
            'data_inicio'       => $this->parseDate($row[array_search('DATA_INICIO', $header)] ?? null),
            'data_fim'          => $this->parseDate($row[array_search('DATA_FIM', $header)] ?? null),
            'capital'           => $this->toFloat($row[array_search('CAPITAL', $header)] ?? 0),
            'premium_total'     => $this->toFloat($row[array_search('PREMIO_TOTAL', $header)] ?? 0),
            'interest'          => $this->toFloat($row[array_search('JUROS', $header)] ?? 0),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    private function mapStatus(?string $value): string
    {
        return match (strtoupper(trim($value ?? ''))) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS' => 'terminated',
            default => 'unknown',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        $invalid = ['NULL', '', 'NORMAL', 'ANULADA', 'TERMINADA', 'INACTIVOS'];
        if (in_array(strtoupper(trim($date)), $invalid)) return null;

        try {
            $date = preg_replace('/\.\d+$/', '', $date);
            return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }
}