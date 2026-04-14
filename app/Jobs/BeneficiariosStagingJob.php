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
    private int $chunkSize = 200;

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

                    // remove linhas lixo tipo "------"
                    $joined = implode('', $line);
                    if ($joined === '' || preg_match('/^-+$/', str_replace(',', '', $joined))) {
                        continue;
                    }

                    // HEADER
                    if (!$header) {
                        $header = $this->normalizeHeader($line);
                        Log::info('HEADER DETECTADO', $header);
                        continue;
                    }

                    $mapped = $this->mapRow($line, $header);

                    // ignora linhas inválidas
                    if (empty($mapped['numero_apolice'])) {
                        continue;
                    }

                    $rows[] = $mapped;

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
            Log::error("❌ Erro import beneficiarios: " . $e->getMessage());
            throw $e;
        }
    }

    private function normalizeHeader(array $line): array
    {
        return array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = trim($h);
            return strtoupper($h);
        }, $line);
    }

    private function mapRow(array $row, array $header): array
    {
        $get = function ($key) use ($row, $header) {

            $key = strtoupper($key);

            foreach ($header as $i => $h) {
                if ($h === $key) {
                    $val = $row[$i] ?? null;
                    $val = trim($val ?? '');

                    if ($val === '' || strtoupper($val) === 'NULL') {
                        return null;
                    }

                    return $val;
                }
            }

            return null;
        };

        return [
            'codigo_produto'                => $get('CODIGO_PRODUTO'),
            'descricao_produto'             => $get('DESCRICAO_PRODUTO'),
            'numero_apolice'                => $get('NUMERO_APOLICE'),
            'codigo_beneficiario'           => $get('CODIGO_BENEFICIARIO'),
            'nome_beneficiario'             => $get('NOME_BENEFICIARIO'),
            'tipo_beneficiario'            => $get('TIPO_BENEFICIARIO'),
            'percentagem_atribuida'        => $this->toFloat($get('PERCENTAGEM_ATRIBUIDA')),
            'pais_residencia_beneficiario' => $get('PAIS_RESIDENCIA_BENEFICIARIO'),
            'parentesco_beneficiario'      => $get('PARENTESCO_BENEFICIARIO'),
            'codigo_situacao_apolice'      => $get('CODIGO_SITUACAO_APOLICE'),
            'situacao_apolice'             => $get('SITUACAO_APOLICE'),
            'data_atualizacao_beneficiario'=> $this->parseDate($get('DATA_ATUALIZACAO_BENEFICIARIO')),
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ];
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;

        try {
            $date = explode('.', $date)[0];
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        return (float) str_replace([' ', ','], ['', '.'], $value ?? 0);
    }
}