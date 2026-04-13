<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
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
    private int $chunkSize = 100;

    public function handle()
    {
        try {
            $files = collect(scandir(base_path()))
                ->filter(fn($file) =>
                    str_starts_with(strtolower($file), 'apolices_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (empty($files)) {
                Log::warning("Nenhum CSV encontrado no diretório base");
                return;
            }

            foreach ($files as $filePath) {
                if (!file_exists($filePath)) continue;

                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    Log::error("Erro ao abrir CSV: {$filePath}");
                    continue;
                }

                Log::info("📄 Importando CSV: {$filePath}");

                // 🔹 Header
                $header = null;
                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    $line = array_map('trim', $line);
                    if (empty(array_filter($line))) continue;
                    if (str_contains(implode(',', $line), '---')) continue;

                    $header = array_map(fn($h) => strtoupper($h), $line);
                    break;
                }

                if (!$header) {
                    Log::error("Header CSV inválido: {$filePath}");
                    fclose($handle);
                    continue;
                }

                $rows = [];
                $inserted = 0;

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $row = array_map('trim', $row);

                    if (empty(array_filter($row))) continue;
                    if (str_contains(implode(',', $row), '---')) continue;

                    if (count($row) !== count($header)) {
                        Log::warning("Linha ignorada (colunas diferentes do header) no arquivo {$filePath}");
                        continue;
                    }

                    $mappedRow = $this->mapRow($row, $header);

                    if (!$mappedRow['numero_cliente'] || !$mappedRow['numero_apolice']) {
                        continue;
                    }

                    $rows[] = $mappedRow;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policies_staging')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];

                        Log::info("📦 Inseridos {$inserted} registros do CSV {$filePath}");
                        gc_collect_cycles();
                    }
                }

                if (!empty($rows)) {
                    DB::table('policies_staging')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);

                Log::info("✅ CSV importado: {$filePath} | Total: {$inserted}");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro na importação: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function mapRow(array $row, array $header): array
    {
        $get = function ($key) use ($row, $header) {
            $index = array_search($key, $header);
            return $index !== false ? $row[$index] : null;
        };

        return [
            // 🔑 Principais
            'numero_cliente' => $get('NUMERO_CLIENTE'),
            'numero_apolice' => $get('NUMERO_APOLICE'),

            // 🧩 Estrutura
            'codigo_ramo' => $get('CODIGO_RAMO'),
            'descricao_ramo' => $get('DESCRICAO_RAMO'),
            'codigo_produto' => $get('CODIGO_PRODUTO'),
            'descricao_produto' => $get('DESCRICAO_PRODUTO'),
            'codigo_canal' => $get('CODIGO_CANAL'),
            'descricao_canal' => $get('DESCRICAO_CANAL'),
            'codigo_agente' => $get('CODIGO_AGENTE'),
            'descricao_agente' => $get('DESCRICAO_AGENTE'),

            // 📊 Estado
            'estado_apolice' => $this->mapStatus($get('ESTADO_APOLICE')),

            // 📅 Datas
            'data_inicio' => $this->parseDate($get('DATA_INICIO')),
            'data_fim' => $this->parseDate($get('DATA_FIM')),
            'data_proxima_renovacao' => $this->parseDate($get('DATA_PROXIMA_RENOVACAO')),
            'data_proximo_vencimento' => $this->parseDate($get('DATA_PROXIMO_VENCIMENTO')),
            'data_anulacao' => $this->parseDate($get('DATA_ANULACAO')),

            // 💰 Financeiros
            'moeda' => $get('MOEDA'), 
            'capital' => $this->toFloat($get('CAPITAL')),
            'capital_liquido_cosseguro' => $this->toFloat($get('CAPITAL_LIQUIDO_COSSEGURO')),
            'premio_simples' => $this->toFloat($get('PREMIO_SIMPLES')),
            'premium_total' => $this->toFloat($get('PREMIO_TOTAL')),
            'encargos' => $this->toFloat($get('ENCARGOS')),
            'outros_encargos' => $this->toFloat($get('OUTROS_ENCARGOS')),
            'interest' => $this->toFloat($get('JUROS')),

            // 📄 Controlo
            'numero_acta' => $get('NUMERO_ACTA'),
            'motivo_anulacao' => $get('MOTIVO_ANULACAO'),
            'submotivo_anulacao' => $get('SUBMOTIVO_ANULACAO'),

            // 🕒 Timestamps
            'created_at' => now(),
            'updated_at' => now(),
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
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return is_numeric($value) ? (float)$value : 0.0;
    }
}