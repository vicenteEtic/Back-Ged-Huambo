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

class BeneficiariosStagingJob implements ShouldQueue
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
                    str_starts_with(strtolower($file), 'beneficiarios') && 
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
                    if (!is_numeric($mappedRow['numero_apolice'])) {
                        continue;
                    }

                    $rows[] = $mappedRow;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('beneficiarios_staging')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('beneficiarios_staging')->insert($rows);
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
    'codigo_produto'                   => $get('Codigo_Produto'),
    'descricao_produto'                => $get('Descricao_Produto'),
    'numero_apolice'                  => $get('Numero_Apolice'),
    'codigo_beneficiario'             => $get('Codigo_Beneficiario'),
    'nome_beneficiario'               => $get('Nome_Beneficiario'),
    'tipo_beneficiario'               => $get('Tipo_Beneficiario'),
    'percentagem_atribuida'           => $this->toFloat($get('Percentagem_Atribuida')),
    'pais_residencia_beneficiario'    => $get('Pais_Residencia_Beneficiario'),
    'parentesco_beneficiario'         => $get('Parentesco_Beneficiario'),
    'codigo_situacao_apolice'         => $get('Codigo_Situacao_Apolice'),
    'situacao_apolice'                => $get('Situacao_Apolice'),
    'data_atualizacao_beneficiario'   => $this->parseDate($get('Data_Atualizacao_Beneficiario')),
    'created_at'                      => now(),
    'updated_at'                      => now(),
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