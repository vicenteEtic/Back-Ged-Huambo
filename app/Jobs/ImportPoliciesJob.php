<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessCustomerPoliciesJob;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hora
    public $tries = 5;

    public function handle()
    {
        $files = glob(base_path('Apolices_*.csv'));

        if (empty($files)) {
            Log::error("Nenhum CSV encontrado em base_path");
            return;
        }

        $customersData = [];

        foreach ($files as $path) {
            if (!file_exists($path)) continue;

            if (($handle = fopen($path, 'r')) === false) {
                Log::error("Erro ao abrir CSV: {$path}");
                continue;
            }

            Log::info("📄 Processando CSV: {$path}");

            $header = fgetcsv($handle, 0, ',');
            $header = array_map(fn($h) => strtolower(trim($h)), $header);

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row)) || str_contains($row[0], '---')) continue;

                $data = $this->mapCsvRow($header, $row);
                if (empty($data['numero_cliente']) || empty($data['numero_apolice'])) continue;

                $customerId = $data['numero_cliente'];

                // Agrupa todas as apólices por cliente
                $customersData[$customerId][] = $data;
            }

            fclose($handle);
        }

        // Dispara job por cliente com todo histórico
        foreach ($customersData as $customerNumber => $policies) {
            $customer = Entities::where('customer_number', $customerNumber)->first();
            if (!$customer) {
                Log::warning("Cliente não encontrado: {$customerNumber}");
                continue;
            }

            ProcessCustomerPoliciesJob::dispatch($customer->id, $policies);
            Log::info("📬 Job disparado para cliente {$customerNumber} com " . count($policies) . " apólices");
        }

        Log::info("✅ Todos os clientes processados e jobs disparados");
    }

    private function mapCsvRow(array $header, array $row): array
    {
        $data = [];
        foreach ($header as $i => $column) {
            $value = $row[$i] ?? null;
            if ($value === 'NULL') $value = null;

            $column = strtolower($column);

            switch ($column) {
                case 'numero_apolice': $data['numero_apolice'] = $value; break;
                case 'numero_cliente': $data['numero_cliente'] = $value; break;
                case 'descricao_produto': $data['descricao_produto'] = strtoupper(trim($value)); break;
                case 'estado_apolice': $data['estado_apolice'] = $this->mapStatus($value); break;
                case 'data_inicio': $data['data_inicio'] = $this->parseDate($value); break;
                case 'data_fim': $data['data_fim'] = $this->parseDate($value); break;
                case 'capital': $data['capital'] = $this->toFloat($value); break;
                case 'premio_total': $data['premium_total'] = $this->toFloat($value); break;
                case 'juros': $data['interest'] = $this->toFloat($value); break;
            }
        }
        return $data;
    }

    private function mapStatus($value): string
    {
        $status = strtoupper(trim($value ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS','Anulada','Terminada' => 'terminated',
            default => 'unknown',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        return substr(trim($date), 0, 19); // YYYY-MM-DD HH:MM:SS
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }
}