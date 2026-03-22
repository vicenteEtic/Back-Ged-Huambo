<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Jobs\ProcessCustomerPoliciesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function handle()
    {
        $files = glob(base_path('Apolices_*.csv'));

        foreach ($files as $path) {

            Log::info("📄 Processando CSV: {$path}");

            if (($handle = fopen($path, 'r')) === false) {
                Log::error("Não foi possível abrir: {$path}");
                continue;
            }

            $header = fgetcsv($handle);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); // remove BOM
            $header = array_map(fn($h) => strtolower(trim($h)), $header);

            $customers = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row))) continue;
                if (str_contains($row[0], '---')) continue;

                $data = $this->mapRow($header, $row);

                if (empty($data['numero_cliente'])) continue;

                $customers[$data['numero_cliente']][] = $data;
            }

            fclose($handle);

            // 🔥 Dispatch por cliente
            foreach ($customers as $customerNumber => $policies) {
                $customer = Entities::where('customer_number', $customerNumber)->first();
                if (!$customer) {
                    Log::warning("Cliente não encontrado: {$customerNumber}");
                    continue;
                }

                ProcessCustomerPoliciesJob::dispatch($customer, $policies)
                    ->onQueue('high');
            }
        }
    }

    private function mapRow(array $header, array $row): array
    {
        $data = [];

        foreach ($header as $i => $column) {
            $value = trim($row[$i] ?? null);
            if ($value === 'NULL' || $value === '') $value = null;

            switch ($column) {
                case 'numero_apolice':   $data['numero_apolice'] = $value; break;
                case 'numero_cliente':   $data['numero_cliente'] = $value; break;
                case 'descricao_produto': $data['descricao_produto'] = strtoupper($value); break;
                case 'estado_apolice':  $data['estado_apolice'] = strtoupper($value); break;
                case 'data_inicio':     $data['data_inicio'] = $this->cleanDate($value); break;
                case 'data_fim':        $data['data_fim'] = $this->cleanDate($value); break;
                case 'capital':         $data['capital'] = $this->toFloat($value); break;
                case 'premio_total':    $data['premium_total'] = $this->toFloat($value); break;
                case 'juros':           $data['interest'] = $this->toFloat($value); break;
            }
        }

        return $data;
    }

    private function toFloat($value): float
    {
        return is_numeric($value) ? (float)$value : 0;
    }

    private function cleanDate(?string $value): ?string
    {
        if (!$value) return null;
        return substr($value, 0, 19); // remove milissegundos
    }
}