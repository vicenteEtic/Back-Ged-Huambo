<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = 60;

    protected CustomerKYTService $kytService;

    public function handle(CustomerKYTService $kytService)
    {
        $this->kytService = $kytService;

        $path = base_path('apolices_vida.csv');

        if (!file_exists($path)) {
            Log::error("Arquivo CSV não encontrado: {$path}");
            return;
        }

        if (($handle = fopen($path, 'r')) === false) {
            Log::error("Erro ao abrir CSV: {$path}");
            return;
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            Log::error("CSV vazio ou cabeçalho inválido: {$path}");
            fclose($handle);
            return;
        }


        $header = array_map('trim', $header);
        $currentCustomerId = null;
        $policiesBuffer = [];

      while (($row = fgetcsv($handle, 0, ',')) !== false) {

    if (empty(array_filter($row))) {
        continue;
    }

    // ignora linha separadora
    if (isset($row[0]) && str_contains($row[0], '----')) {
        continue;
    }

            $dataImport = $this->mapCsvRow($header, $row);

            if (empty($dataImport['customer_number']) || empty($dataImport['contract_number'])) {
                Log::warning("Linha ignorada: Numero_Apolice ou Numero_Cliente vazio");
                continue;
            }

            // Processa buffer quando mudamos de cliente
            if ($currentCustomerId && $currentCustomerId != $dataImport['customer_number']) {
                $this->processCustomer($currentCustomerId, $policiesBuffer);
                $policiesBuffer = []; // limpa buffer
            }

            $currentCustomerId = $dataImport['customer_number'];
            $policiesBuffer[] = $dataImport;
        }

        // Processa último cliente
        if ($currentCustomerId && !empty($policiesBuffer)) {
            $this->processCustomer($currentCustomerId, $policiesBuffer);
        }

        fclose($handle);
        Log::info("Importação e execução KYT concluída com sucesso.");
    }

    private function processCustomer(string $customerId, array $policies)
    {
        $customer = new Entities();
        $customer->customer_number = $customerId;
        $customer->social_denomination = "Cliente #$customerId";

        try {
            $this->kytService->runAllChecksMemory($customer, $policies);
        } catch (\Exception $e) {
            Log::error("Erro KYT para cliente {$customerId}: " . $e->getMessage());
        }
    }

    private function mapCsvRow(array $header, array $row): array
    {
        $dataImport = [];
        foreach ($header as $idx => $column) {

            $value = $row[$idx] ?? null;

            switch (strtolower($column)) {

                case 'numero_apolice':
                    $dataImport['numero_apolice'] = $value;
                    break;

                case 'numero_cliente':
                    $dataImport['numero_cliente'] = $value;
                    break;

                case 'descricao_produto':
                    $dataImport['descricao_produto'] = $value;
                    break;

                case 'estado_apolice':

                    $status = strtoupper(trim($value));

                    switch ($status) {

                        case 'NORMAL':
                            $status = 'active';
                            break;

                        case 'C/ CARTA':
                            $status = 'cancelled';
                            break;

                        case 'ANULADA':
                            $status = 'terminated';
                            break;

                        default:
                            $status = 'unknown';
                    }

                    $dataImport['estado_apolice'] = $status;

                    break;

                case 'data_inicio':
                    $dataImport['data_inicio'] = $this->parseDate($value);
                    break;

                case 'data_fim':
                    $dataImport['data_fim'] = $this->parseDate($value);
                    break;

                case 'capital':
                    $dataImport['capital'] = floatval($value ?? 0);
                    break;

                case 'premio_total':
                    $dataImport['premium_total'] = floatval($value ?? 0);
                    break;

                case 'encargos':
                    $dataImport['encargos'] = floatval($value ?? 0);
                    break;

                case 'juros':
                    $dataImport['interest'] = floatval($value ?? 0);
                    break;
            }
        }

        return $dataImport;
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        try {
            return Carbon::parse(substr($date, 0, 10))->format('Y-m-d'); // Remove hora e microssegundos
        } catch (\Exception $e) {
            return null;
        }
    }
}
