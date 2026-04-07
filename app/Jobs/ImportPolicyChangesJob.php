<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPolicyChangesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 3h
    public $tries = 3;
    private int $chunkSize = 100;

    public function handle()
    {
        try {
            // 🔹 Buscar CSVs de alterações
            $files = collect(scandir(base_path()))
                ->filter(fn($file) =>
                    str_starts_with(strtolower($file), 'alteracoes_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (empty($files)) {
                Log::warning("Nenhum CSV de alterações encontrado");
                return;
            }

            foreach ($files as $filePath) {

                if (!file_exists($filePath)) continue;

                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    Log::error("Erro ao abrir CSV: {$filePath}");
                    continue;
                }

                Log::info("📄 Importando alterações: {$filePath}");

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
                    Log::error("Header inválido: {$filePath}");
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
                        Log::warning("Linha inválida (colunas diferentes): {$filePath}");
                        continue;
                    }

                    $mapped = $this->mapRow($row, $header);

                    if (!$mapped['numero_apolice']) continue;

                    $rows[] = $mapped;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policy_changes_staging')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];

                        Log::info("📦 {$inserted} alterações inseridas ({$filePath})");
                        gc_collect_cycles();
                    }
                }

                if (!empty($rows)) {
                    DB::table('policy_changes_staging')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);

                Log::info("✅ Importação concluída: {$filePath} | Total: {$inserted}");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro ao importar alterações: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function mapRow(array $row, array $header): array
    {
        return [
            'numero_apolice' => $row[array_search('NUMERO_APOLICE', $header)] ?? null,

            'data_alteracao' => $this->parseDate(
                $row[array_search('DATA_ALTERACAO', $header)] ?? null
            ),

            'tipo_alteracao' => strtoupper(
                trim($row[array_search('TIPO_ALTERACAO', $header)] ?? '')
            ),

            'valor_anterior' => $this->toFloat(
                $row[array_search('VALOR_ANTERIOR', $header)] ?? 0
            ),

            'novo_valor' => $this->toFloat(
                $row[array_search('NOVO_VALOR', $header)] ?? 0
            ),

            'percentual_variacao' => $this->toFloat(
                $row[array_search('PERCENTUAL_VARIACAO', $header)] ?? 0
            ),

            'motivo_alteracao' => trim(
                $row[array_search('MOTIVO_ALTERACAO', $header)] ?? ''
            ),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;

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