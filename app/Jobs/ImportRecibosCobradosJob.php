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

class ImportRecibosCobradosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; 
    private int $chunkSize = 100;

    public function __construct() {}

    public function handle()
    {
        try {
            // Procura ficheiros que comecem por 'recibos_cobrados'
            $files = collect(scandir(base_path()))
                ->filter(fn($file) => 
                    str_starts_with(strtolower($file), 'recibos_cobrados') && 
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
                    
                    // 1. Pular linhas vazias ou com traços (o problema que vimos no Linux)
                    if (empty(array_filter($line)) || (isset($line[0]) && str_starts_with($line[0], '---'))) {
                        continue;
                    }

                    // 2. Definir o Header
                    if (!$header) {
                        $header = array_map(fn($h) => strtoupper($h), $line);
                        continue;
                    }

                    // 3. Mapear e validar
                    $mappedRow = $this->mapRow($line, $header);

                    // 4. Validação: Só processa se houver um número de apólice ou recibo válido
                    if (empty($mappedRow['recibo']) && empty($mappedRow['numero_apolice'])) {
                        continue;
                    }

                    $rows[] = $mappedRow;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('recibos_cobrados')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('recibos_cobrados')->insert($rows);
                    $inserted += count($rows);
                }

                fclose($handle);
                Log::info("✅ Importação Recibos: {$filePath} | Total: {$inserted}");
            }
        } catch (\Throwable $e) {
            Log::error("❌ Erro no ImportRecibosCobradosJob: " . $e->getMessage());
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
            'id_transacao'                => $get('ID_TRANSACAO'),
            'recibo'                      => $get('RECIBO'),
            'numero_apolice'              => $get('NUMERO_APOLICE'),
            'data_pagamento'              => $this->parseDate($get('DATA_PAGAMENTO')),
            'valor_pago'                  => $this->toFloat($get('VALOR_PAGO')),
            'metodo_pagamento'            => $get('METODO_PAGAMENTO'),
            'iban_origem'                 => $get('IBAN_ORIGEM'),
            'pais_iban_origem'            => $get('PAIS_IBAN_ORIGEM'),
            'codigo_pagador'              => $get('CODIGO_PAGADOR'),
            'nome_pagador'                => $get('NOME_PAGADOR'),
            'nif_pagador'                 => $get('NIF_PAGADOR'),
            'relacao_com_tomador'         => $get('RELACAO_COM_TOMADOR'),
            'indicador_pagamento_terceiro' => $get('INDICADOR_PAGAMENTO_TERCEIRO'),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ];
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        try {
            $date = explode('.', $date)[0]; // Limpa microssegundos
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if (!$value) return 0;
        return (float) str_replace([' ', ','], ['', ''], $value);
    }

    private function toBoolean($value): bool
    {
        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'sim', 's', 'yes', 'y']);
    }
}