<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 3h
    public $tries = 3;
    private int $chunkSize = 100; // Inserção por batch

    public function handle()
    {
        try {
            $files = collect(scandir(base_path()))
            ->filter(function ($file) {
                $fileLower = strtolower($file);
        
                return str_starts_with($fileLower, 'apolices_')
                    && str_ends_with($fileLower, '.csv');
            })
            ->map(fn($file) => base_path($file))
            ->values()
            ->toArray();

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
                $skippedEmpty = 0;
                $skippedNoCliente = 0;
                $skippedNoApolice = 0;

                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    $line = array_map('trim', $line);

                    // Linha totalmente vazia
                    if (empty(array_filter($line))) {
                        $skippedEmpty++;
                        continue;
                    }

                    // Ignorar linhas com "---"
                    if (str_contains(implode(',', $line), '---')) continue;

                    // Cabeçalho
                    if (!$header) {
                        $header = array_map(fn($h) => strtoupper(trim($h)), $line);
                        continue;
                    }

                    $row = $this->mapRow($line, $header);

                    // Checagens de integridade
                    if (!$row['numero_cliente']) {
                        $skippedNoCliente++;
                        continue;
                    }
                    if (!$row['numero_apolice']) {
                        $skippedNoApolice++;
                        continue;
                    }

                    $rows[] = $row;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policies_staging')->upsert($rows,$rows);
                        $inserted += count($rows);
                        $rows = [];
                        gc_collect_cycles();
                    }
                }

                // Último batch
                if (!empty($rows)) {
                    DB::table('policies_staging')->upsert($rows,$rows);
                    $inserted += count($rows);
                    $rows = [];
                }

                fclose($handle);

                Log::info("✅ Arquivo finalizado: {$filePath}");
                Log::info("   Total inserido: {$inserted}");
                Log::info("   Pulados (vazios): {$skippedEmpty}");
                Log::info("   Pulados (sem numero_cliente): {$skippedNoCliente}");
                Log::info("   Pulados (sem numero_apolice): {$skippedNoApolice}");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro na importação: " . $e->getMessage());
            $this->fail($e);
        }
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
            return Carbon::parse($date)->format('Y-m-d H:i:s');
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