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

class ImportApolAnuladaEstornoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800;
    public $tries = 10;
    private int $chunkSize = 100;

    public function handle()
    {
        try {
            // 🔹 Buscar arquivos CSV
            $files = collect(scandir(base_path()))
                ->filter(fn($file) =>
                    str_starts_with(strtolower($file), 'apol_anulada_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (empty($files)) {
                Log::warning("Nenhum CSV de estorno encontrado");
                return;
            }

            foreach ($files as $filePath) {
                $handle = fopen($filePath, 'r');
                if (!$handle) continue;

                Log::info("📄 Processando estornos: {$filePath}");

                $header = $this->readHeader($handle);
                if (!$header) continue;

                $rows = [];
                $inserted = 0;

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    // Ignorar linhas de separadores
                    if ($this->isSeparatorLine($row)) continue;

                    $mapped = $this->mapRow($row, $header);
                    if (!$mapped) continue;

                    $rows[] = $mapped;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('apol_anulada_estorno')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('apol_anulada_estorno')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);
                Log::info("✅ {$filePath} -> {$inserted} registros inseridos");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro no import de estornos: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function readHeader($handle): ?array
    {
        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            $line = array_map('trim', $line);

            // Ignora linhas vazias ou separadores
            if (empty(array_filter($line))) continue;
            if ($this->isSeparatorLine($line)) continue;

            return array_map(fn($h) => strtoupper($h), $line);
        }

        return null;
    }

    private function isSeparatorLine(array $line): bool
    {
        $lineStr = implode('', $line);
        return str_contains($lineStr, '---');
    }

    private function mapRow(array $row, array $header): ?array
    {
        try {
            if (count($row) !== count($header)) return null;

            $numeroApolice = $this->get($row, $header, 'N_APOLICE');
            if (!$numeroApolice) return null;

            $valor = $this->toFloat($this->get($row, $header, 'VALOR_TOTAL'));

            return [
                'n_apolice' => $numeroApolice,
                'idtitular' => $this->get($row, $header, 'IDTITULAR'),
                'data_anulacao' => $this->parseDate($this->get($row, $header, 'DATA_ANULACAO')),
                'data_pagamento' => $this->parseDate($this->get($row, $header, 'DATA_PAGAMENTO')),
                'razao' => trim($this->get($row, $header, 'RAZAO')),
                'subrazao' => trim($this->get($row, $header, 'SUBRAZAO')),
                'recibo_estorno' => $this->get($row, $header, 'RECIBO_ESTORNO'),
                'valor_total' => $valor,
                'situacao' => strtoupper(trim($this->get($row, $header, 'SITUACAO'))),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Linha ignorada (estorno)", ['erro' => $e->getMessage()]);
            return null;
        }
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