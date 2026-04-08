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
    private int $chunkSize = 100;

    public function __construct() {}

    public function handle()
    {
        try {
            $files = collect(scandir(base_path()))
                ->filter(fn($file) => 
                    str_starts_with(strtolower($file), 'apol_anulada_') && 
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file));

            foreach ($files as $filePath) {
                if (!file_exists($filePath)) continue;

                $handle = fopen($filePath, 'r');
                $header = null;
                $rows = [];
                $inserted = 0;

                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    $line = array_map('trim', $line);
                    
                    // 1. Ignora linhas vazias ou que contenham apenas os traços (---------)
                    if (empty(array_filter($line)) || str_contains(implode('', $line), '---')) {
                        continue;
                    }

                    // 2. Define o Header na primeira linha válida
                    if (!$header) {
                        $header = array_map(fn($h) => strtoupper($h), $line);
                        continue;
                    }

                    // 3. Mapeia os dados
                    $mappedRow = $this->mapRow($line, $header);

                    // 4. Validação crucial para evitar o erro 1366 (Integer value)
                    if (!is_numeric($mappedRow['idtitular'])) {
                        continue;
                    }

                    $rows[] = $mappedRow;

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
                Log::info("✅ Importação concluída: {$filePath} | Registos: {$inserted}");
            }
        } catch (\Throwable $e) {
            Log::error("❌ Erro: " . $e->getMessage());
            throw $e;
        }
    }

    private function mapRow(array $row, array $header): array
    {
        $get = function ($key) use ($row, $header) {
            $index = array_search($key, $header);
            $val = ($index !== false && isset($row[$index])) ? trim($row[$index]) : null;
            return ($val === 'NULL' || $val === '') ? null : $val;
        };

        return [
            'idtitular'      => $get('IDTITULAR'),
            'n_apolice'      => $get('N_APOLICE'),
            'data_anulacao'  => $this->parseDate($get('DATA_ANULACAO')),
            'data_pagamento' => $this->parseDate($get('DATA_PAGAMENTO')),
            'razao'          => $get('RAZAO'),
            'subrazao'       => $get('SUBRAZAO'),
            'situacao'       => $get('SITUACAO'),
            'recibo_estorno' => $get('RECIBO_ESTORNO'),
            'valor_total'    => $this->toFloat($get('VALOR_TOTAL')),
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        try {
            // Remove microssegundos se existirem para evitar erro de parse
            $date = explode('.', $date)[0];
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        return (float) str_replace(',', '', $value ?? 0);
    }
}