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

    public $timeout = 10800; // 3h
    public $tries = 3;
    private int $chunkSize = 100;

    /**
     * O construtor agora é vazio para evitar o erro de argumentos no dispatch()
     */
    public function __construct()
    {
    }

    public function handle()
    {
        try {
            // Escaneia o diretório base procurando arquivos que começam com 'apol_anulada_'
            $files = collect(scandir(base_path()))
                ->filter(fn($file) =>
                    str_starts_with(strtolower($file), 'apol_anulada_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (empty($files)) {
                Log::warning("Nenhum CSV 'apol_anulada_' encontrado no diretório base");
                return;
            }

            foreach ($files as $filePath) {
                if (!file_exists($filePath)) continue;

                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    Log::error("Erro ao abrir CSV: {$filePath}");
                    continue;
                }

                Log::info("📄 Importando CSV de Estornos: {$filePath}");

                // 1. Localizar o Header (Ignora linhas vazias ou com traços)
                $header = null;
                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    $line = array_map('trim', $line);
                    if (empty(array_filter($line))) continue;
                    if (str_contains(implode(',', $line), '---')) continue;

                    $header = array_map(fn($h) => strtoupper($h), $line);
                    break;
                }

                if (!$header) {
                    Log::error("Header CSV inválido ou não encontrado: {$filePath}");
                    fclose($handle);
                    continue;
                }

                $rows = [];
                $inserted = 0;

                // 2. Processar Dados
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $row = array_map('trim', $row);

                    // Ignora linhas de separação (sujeira) ou vazias
                    if (empty(array_filter($row))) continue;
                    if (str_contains(implode(',', $row), '---')) continue;

                    // Garante que a linha tem o mesmo número de colunas que o header
                    if (count($row) !== count($header)) {
                        continue;
                    }

                    $mappedRow = $this->mapRow($row, $header);

                    // Validação mínima para não inserir lixo
                    if (!$mappedRow['idtitular'] || !$mappedRow['n_apolice']) {
                        continue;
                    }

                    $rows[] = $mappedRow;

                    // Inserção em lotes (Chunks)
                    if (count($rows) >= $this->chunkSize) {
                        DB::table('apol_anulada_estorno')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                        gc_collect_cycles();
                    }
                }

                // Insere o restante
                if (!empty($rows)) {
                    DB::table('apol_anulada_estorno')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);
                Log::info("✅ CSV Finalizado: {$filePath} | Total: {$inserted} estornos.");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro na importação de estornos: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function mapRow(array $row, array $header): array
    {
        $get = function ($key) use ($row, $header) {
            $index = array_search(strtoupper($key), $header);
            return $index !== false ? $row[$index] : null;
        };

        return [
            'idtitular'       => (int)$get('IDTITULAR'),
            'n_apolice'       => $get('N_APOLICE'),
            'data_anulacao'   => $this->parseDate($get('DATA_ANULACAO')),
            'data_pagamento'  => $this->parseDate($get('DATA_PAGAMENTO')),
            'razao'           => $get('RAZAO'),
            'subrazao'        => $get('SUBRAZAO'),
            'situacao'        => $get('SITUACAO'),
            'recibo_estorno'  => $get('RECIBO_ESTORNO'),
            'valor_total'     => $this->toFloat($get('VALOR_TOTAL')),
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date || in_array(strtoupper(trim($date)), ['NULL', '', '?', '-------'])) {
            return null;
        }

        try {
            return Carbon::parse(trim($date))->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if (!$value) return 0.0;
        $value = str_replace(['.', ','], ['', '.'], trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }
}