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

class ImportPolicyChangesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800;
    public $tries = 3;
    private int $chunkSize = 100;

    public function handle()
    {
        try {

            $files = collect(scandir(base_path()))
                ->filter(fn($file) =>
                    str_starts_with(strtolower($file), 'alteracoes_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (empty($files)) {
                Log::warning("Nenhum CSV encontrado");
                return;
            }

            foreach ($files as $filePath) {

                $handle = fopen($filePath, 'r');
                if (!$handle) continue;

                Log::info("📄 Processando: {$filePath}");

                $header = $this->readHeader($handle);
                if (!$header) continue;

                $rows = [];
                $inserted = 0;

                while (($row = fgetcsv($handle, 0, ',')) !== false) {

                    $mapped = $this->mapRow($row, $header);

                    if (!$mapped) continue;

                    $rows[] = $mapped;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policy_changes_staging')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('policy_changes_staging')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);

                Log::info("✅ {$filePath} -> {$inserted} registros");
            }

        } catch (\Throwable $e) {
            Log::error("Erro: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function readHeader($handle): ?array
    {
        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            $line = array_map('trim', $line);

            if (empty(array_filter($line))) continue;
            if (str_contains(implode(',', $line), '---')) continue;

            return array_map(fn($h) => strtoupper($h), $line);
        }

        return null;
    }

    private function mapRow(array $row, array $header): ?array
    {
        try {

            if (count($row) !== count($header)) return null;

            $numero = $this->get($row, $header, 'NUMERO_APOLICE');
            if (!$numero) return null;

            $anterior = $this->toFloat($this->get($row, $header, 'VALOR_ANTERIOR'));
            $novo     = $this->toFloat($this->get($row, $header, 'NOVO_VALOR'));

            $percentual = $this->calculatePercentual($anterior, $novo);

            return [
                'numero_apolice' => $numero,
                'data_alteracao' => $this->parseDate($this->get($row, $header, 'DATA_ALTERACAO')),
                'tipo_alteracao' => strtoupper(trim($this->get($row, $header, 'TIPO_ALTERACAO'))),

                'valor_anterior' => $anterior,
                'novo_valor' => $novo,
                'percentual_variacao' => $percentual,

                'motivo_alteracao' => trim($this->get($row, $header, 'MOTIVO_ALTERACAO')),

                'created_at' => now(),
                'updated_at' => now(),
            ];

        } catch (\Throwable $e) {
            Log::warning("Linha ignorada", ['erro' => $e->getMessage()]);
            return null;
        }
    }

    private function calculatePercentual(float $anterior, float $novo): ?float
    {
        if ($anterior <= 0) {
            Log::warning('Valor anterior inválido', [
                'anterior' => $anterior,
                'novo' => $novo
            ]);
            return null;
        }

        $percentual = (($novo - $anterior) / $anterior) * 100;

        // 🔥 filtro anti-lixo
        if (abs($percentual) > 1000) {
            Log::warning('Percentual absurdo', [
                'anterior' => $anterior,
                'novo' => $novo,
                'percentual' => $percentual
            ]);
            return null;
        }

        return round($percentual, 2);
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;

        try {
            return Carbon::parse(preg_replace('/\.\d+$/', '', $date))
                ->format('Y-m-d H:i:s');
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

    private function get(array $row, array $header, string $key): ?string
    {
        $index = array_search($key, $header);
        return $index !== false ? ($row[$index] ?? null) : null;
    }
}