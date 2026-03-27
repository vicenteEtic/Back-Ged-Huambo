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
            // 🔹 Buscar todos os CSVs que começam com 'apolices_' e terminam com '.csv'
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
    
                // Encontrar header válido
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
                    if (!$mappedRow['numero_cliente'] || !$mappedRow['numero_apolice']) continue;
    
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
                Log::info("✅ CSV importado: {$filePath} | Total registros inseridos: {$inserted}");
            }
    
        } catch (\Throwable $e) {
            Log::error("❌ Erro na importação: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function mapRow(array $row, array $header): array
    {
        $map = [
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
        return $map;
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