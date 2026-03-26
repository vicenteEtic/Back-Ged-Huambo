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

class ImportPoliciesJob implements ShouldQueue
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
                    str_starts_with(strtolower($file), 'apolices_') &&
                    str_ends_with(strtolower($file), '.csv')
                )
                ->map(fn($file) => base_path($file))
                ->values()
                ->toArray();

            if (!$files) {
                Log::warning("Nenhum CSV encontrado");
                return;
            }

            foreach ($files as $filePath) {
                Log::info("📄 Importando: {$filePath}");
                if (!file_exists($filePath)) continue;

                $handle = fopen($filePath, 'r');
                if (!$handle) continue;

                $header = null;
                $rows = [];

                while (($line = fgetcsv($handle, 0, ',')) !== false) {
                    // 🔹 Limpeza forte de encoding
                    $line = array_map(fn($v) => $this->cleanString($v), $line);

                    if (empty(array_filter($line))) continue;
                    if (str_contains(implode(',', $line), '---')) continue;

                    if (!$header) {
                        $header = array_map(fn($h) => strtoupper(trim($h)), $line);
                        continue;
                    }

                    $row = $this->mapRow($line, $header);
                    if (!$row['numero_cliente'] || !$row['numero_apolice']) continue;

                    // mapeia o ramo e garante UTF-8
                    $row['ramo'] = $this->mapRamo($row['descricao_produto']);

                    $rows[] = $row;

                    if (count($rows) >= $this->chunkSize) {
                        DB::table('policies_staging')->upsert($rows, ['numero_apolice']);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('policies_staging')->upsert($rows, ['numero_apolice']);
                }

                fclose($handle);
                Log::info("✅ Finalizado: {$filePath}");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Erro: " . $e->getMessage());
            $this->fail($e);
        }
    }

    // ================= LIMPEZA FORTE =================
    private function cleanString(?string $value): string
    {
        if (!$value) return '';

        $value = trim($value);
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        $value = preg_replace('/[^\PC\s]/u', '', $value);
        $value = str_replace(['�'], '', $value);

        return $value;
    }

    // ================= MAP ROW =================
    private function mapRow(array $row, array $header): array
    {
        return [
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
    }

    // ================= STATUS =================
    private function mapStatus(?string $value): string
    {
        return match (strtoupper(trim($value ?? ''))) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS' => 'terminated',
            default => 'unknown',
        };
    }

    // ================= DATE =================
    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;

        try {
            return Carbon::parse(preg_replace('/\.\d+$/', '', $date))->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    // ================= FLOAT =================
    private function toFloat($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        return is_numeric($value) ? (float)$value : 0.0;
    }

    // ================= RAMO =================
    private function mapRamo(?string $descricaoProduto): string
    {
        if (!$descricaoProduto) return 'OUTROS';

        $descricao = $this->normalize($descricaoProduto);
        $descricao = $this->cleanString($descricao);

        // busca no banco se já existe
        $ramo = DB::table('produto_para_ramo')
            ->where('descricao_produto', $descricao)
            ->value('ramo');

        if ($ramo) return $ramo;

        $ramo = $this->mapRamoFallback($descricao);

        // salva ou atualiza, evitando duplicados
        DB::table('produto_para_ramo')->updateOrInsert(
            ['descricao_produto' => $descricao],
            [
                'ramo' => $ramo,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return $ramo;
    }

    private function normalize(string $text): string
    {
        $text = strtoupper($this->removeAccents($text));

        // correções comuns
        $text = str_replace(['PRÉMIO', 'PRCIMIO'], 'PREMIO', $text);
        $text = str_replace(['PROTECCAO', 'PROTEC CAO'], 'PROTECCAO', $text);

        return trim($text);
    }

    private function mapRamoFallback(string $descricao): string
    {
        return match (true) {

            str_contains($descricao, 'VIDA'),
            str_contains($descricao, 'PREMIO'),
            str_contains($descricao, 'VARIAVEL'),
            str_contains($descricao, 'FIXO') => 'VIDA',

            str_contains($descricao, 'SAUDE') => 'SAÚDE',

            str_contains($descricao, 'ESCOLAR') => 'ESCOLAR',

            str_contains($descricao, 'VIAGEM') => 'VIAGEM',

            str_contains($descricao, 'PROFISSIONAL') => 'EXTRA PROFISSIONAL',

            str_contains($descricao, 'ASSALTO'),
            str_contains($descricao, 'ROUBO'),
            str_contains($descricao, 'INCENDIO') => 'EXTRA PATRIMONIAL',

            default => 'OUTROS',
        };
    }

    private function removeAccents(string $string): string
    {
        return strtr(
            $string,
            'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇç',
            'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCc'
        );
    }
}