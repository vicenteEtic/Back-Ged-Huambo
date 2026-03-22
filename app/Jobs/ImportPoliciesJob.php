<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;

    public $timeout = 3600; // 1 hora
    public $tries = 5;

    public function handle()
    {
        // Lista de CSVs (pode usar base_path e nomes fixos ou glob)
        $files = glob(base_path('apolices_*.csv')); // pega todos os arquivos que começam com "apolices_"

        if (empty($files)) {
            Log::error("Nenhum CSV encontrado em base_path");
            return;
        }

        foreach ($files as $path) {
            if (!file_exists($path)) {
                Log::error("CSV não encontrado: {$path}");
                continue;
            }

            if (($handle = fopen($path, 'r')) === false) {
                Log::error("Erro ao abrir CSV: {$path}");
                continue;
            }

            Log::info("📄 Processando CSV: {$path}");

            $header = fgetcsv($handle, 0, ',');
            $header = array_map(fn($h) => strtolower(trim($h)), $header);

            $currentCustomerNumber = null;
            $policiesBuffer = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row)) || str_contains($row[0], '---')) continue;

                $data = $this->mapCsvRow($header, $row);
                if (empty($data['numero_cliente']) || empty($data['numero_apolice'])) continue;

                if ($currentCustomerNumber && $currentCustomerNumber !== $data['numero_cliente']) {
                    $this->dispatchCustomerJob($currentCustomerNumber, $policiesBuffer);
                    $policiesBuffer = [];
                }

                $currentCustomerNumber = $data['numero_cliente'];
                $policiesBuffer[] = $data;
            }

            if ($currentCustomerNumber && !empty($policiesBuffer)) {
                $this->dispatchCustomerJob($currentCustomerNumber, $policiesBuffer);
            }

            fclose($handle);
        }

        Log::info("✅ Todos os CSVs processados e jobs individuais disparados");
    }

    private function dispatchCustomerJob(string $customerNumber, array $policies)
    {



        $customer = Entities::where('customer_number', $customerNumber)->first();
        if ($customer) {
            ProcessCustomerPoliciesJob::dispatch($customer, $policies)->onQueue('high');
        }
    }

    private function mapCsvRow(array $header, array $row): array
    {
        $data = [];

        foreach ($header as $i => $column) {
            $value = $row[$i] ?? null;
            if ($value === 'NULL') $value = null;

            switch ($column) {
                case 'numero_apolice':
                    $data['numero_apolice'] = $value;
                    break;
                case 'numero_cliente':
                    $data['numero_cliente'] = $value;
                    break;
                case 'descricao_produto':
                    $data['descricao_produto'] = strtoupper(trim($value));
                    break;
                case 'estado_apolice':
                    $data['estado_apolice'] = $this->mapStatus($value);
                    break;
                case 'data_inicio':
                    $data['data_inicio'] = $this->parseDate($value);
                    break;
                case 'data_fim':
                    $data['data_fim'] = $this->parseDate($value);
                    break;
                case 'capital':
                    $data['capital'] = (float)$value;
                    break;
                case 'premio_total':
                    $data['premium_total'] = (float)$value;
                    break;
                case 'encargos':
                    $data['encargos'] = (float)$value;
                    break;
                case 'juros':
                    $data['interest'] = (float)$value;
                    break;
            }
        }

        return $data;
    }

    private function mapStatus($value): string
    {
        $status = strtoupper(trim($value));
        return match ($status) {
            'NORMAL' => 'active',
            'C/ CARTA' => 'cancelled',
            'ANULADA' => 'terminated',
            default => 'unknown'
        };
    }

    private function parseDate($date)
    {
        return $date ? substr($date, 0, 10) : null;
    }
}