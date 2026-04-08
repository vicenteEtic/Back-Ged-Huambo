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
use Throwable;

class ImportApolAnuladaEstornoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $batchSize = 500;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        if (!file_exists($this->filePath)) {
            Log::error("Arquivo não encontrado: " . $this->filePath);
            return;
        }

        $handle = fopen($this->filePath, "r");
        
        // 1. IGNORA A PRIMEIRA LINHA (Cabeçalho)
        $header = fgetcsv($handle, 0, ","); 

        $rowsToInsert = [];
        $count = 0;

        DB::beginTransaction();

        try {
            // 2. Lê as próximas linhas
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                // Combina o cabeçalho com os dados para acessar por nome
                $row = array_combine($header, $data);

                // 3. VALIDAÇÃO: Ignora se o idtitular não for número (ex: '-------' ou vazio)
                if (!isset($row['idtitular']) || !is_numeric(trim($row['idtitular']))) {
                    continue;
                }

                $rowsToInsert[] = [
                    'created_at'      => now(),
                    'updated_at'      => now(),
                    'data_anulacao'   => $this->parseDate($row['data_anulacao'] ?? null),
                    'data_pagamento'  => $this->parseDate($row['data_pagamento'] ?? null),
                    'idtitular'       => (int)trim($row['idtitular']),
                    'n_apolice'       => $row['n_apolice'] ?? null,
                    'razao'           => $row['razao'] ?? null,
                    'recibo_estorno'  => $row['recibo_estorno'] ?? null,
                    'situacao'        => $row['situacao'] ?? null,
                    'subrazao'        => $row['subrazao'] ?? null,
                    'valor_total'     => $this->parseFloat($row['valor_total'] ?? 0),
                ];

                $count++;

                if ($count % $this->batchSize === 0) {
                    DB::table('apol_anulada_estorno')->insert($rowsToInsert);
                    $rowsToInsert = [];
                }
            }

            if (!empty($rowsToInsert)) {
                DB::table('apol_anulada_estorno')->insert($rowsToInsert);
            }

            DB::commit();
            fclose($handle);
            Log::info("Sucesso! {$count} registros importados.");

        } catch (Throwable $e) {
            DB::rollBack();
            if ($handle) fclose($handle);
            Log::error("Erro na importação: " . $e->getMessage());
            throw $e;
        }
    }

    private function parseFloat($value)
    {
        if (!$value) return 0;
        $cleanValue = str_replace(['.', ','], ['', '.'], trim($value));
        return is_numeric($cleanValue) ? (float)$cleanValue : 0;
    }

    private function parseDate($value)
    {
        if (empty($value) || trim($value) === '?' || trim($value) === '-------') {
            return null;
        }
        try {
            return Carbon::parse(trim($value))->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}