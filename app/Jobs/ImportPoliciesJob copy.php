<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\Transation\PoliciesService;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;

class ImportPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 3600;
    public $backoff = 60;

    protected PoliciesService $policiesService;
    protected CustomerKYTService $kytService;

    public function handle(PoliciesService $policiesService, CustomerKYTService $kytService)
    {
        $this->policiesService = $policiesService;
        $this->kytService = $kytService;

        $path = base_path('apolices_nvida.csv');

        if (!file_exists($path)) {
            Log::error("Arquivo CSV não encontrado: {$path}");
            return;
        }

        $totalInserted = 0;
        $customerIds = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ',');
            if (!$header) {
                Log::error("CSV vazio ou cabeçalho inválido: {$path}");
                return;
            }
            $header = array_map('trim', $header);

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row))) continue;

                $dataImport = [];

                foreach ($header as $idx => $column) {
                    $value = $row[$idx] ?? null;

                    switch (strtolower($column)) {
                        case 'numero_apolice':
                            $dataImport['contract_number'] = $value;
                            break;
                        case 'numero_cliente':
                            $dataImport['customer_number'] = $value;
                            $customerIds[] = $value;
                            break;
                        case 'codigo_ramo':
                            $dataImport['branch_code'] = $value;
                            break;
                        case 'descricao_ramo':
                            $dataImport['branch_desc'] = $value;
                            break;
                        case 'codigo_produto':
                            $dataImport['product_code'] = $value;
                            break;
                        case 'descricao_produto':
                            $dataImport['product_desc'] = $value;
                            break;
                        case 'codigo_canal':
                            $dataImport['channel_code'] = $value;
                            break;
                        case 'descricao_canal':
                            $dataImport['channel_desc'] = $value;
                            break;
                        case 'codigo_agente':
                            $dataImport['agent_code'] = $value;
                            break;
                        case 'descricao_agente':
                            $dataImport['agent_desc'] = $value;
                            break;
                        case 'estado_apolice':
                            $dataImport['status'] = $value;
                            break;
                        case 'data_inicio':
                            $dataImport['start_date'] = $this->parseDate($value);
                            break;
                        case 'data_fim':
                            $dataImport['end_date'] = $this->parseDate($value);
                            break;
                        case 'data_proxima_renovacao':
                            $dataImport['renewal_date'] = $this->parseDate($value);
                            break;
                        case 'data_proximo_vencimento':
                            $dataImport['issue_date'] = $this->parseDate($value);
                            break;
                        case 'moeda':
                            $dataImport['moeda'] = $value;
                            break;
                        case 'capital':
                            $dataImport['capital'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'capital_liquido_cosseguro':
                            $dataImport['capital_liquido_cosseguro'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'premio_simples':
                            $dataImport['premium_simple'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'premio_total':
                            $dataImport['premium_total'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'encargos':
                            $dataImport['charges'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'outros_encargos':
                            $dataImport['outros_encargos'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                        case 'juros':
                            $dataImport['interest'] = is_numeric($value) ? floatval($value) : 0;
                            break;
                    }
                }

                if (empty($dataImport['contract_number']) || empty($dataImport['customer_number'])) {
                    Log::warning("Linha ignorada: Numero_Apolice ou Numero_Cliente vazio");
                    continue;
                }

                try {
                    $this->policiesService->storeOrUpdate(
                        ['contract_number' => $dataImport['contract_number']],
                        $dataImport
                    );
                    $totalInserted++;

                    if ($totalInserted % 1000 === 0) {
                        gc_collect_cycles();
                    }

                } catch (\Exception $e) {
                    Log::error("Falha ao inserir apólice {$dataImport['contract_number']}: " . $e->getMessage());
                }
            }

            fclose($handle);
        }

        Log::info("Importação de policies concluída! Total inserido/atualizado: {$totalInserted}");

        $customerIds = array_unique($customerIds);
        foreach ($customerIds as $id) {
            try {
                $customer = Entities::where('customer_number', $id)->first();
                if ($customer) {
                    $this->kytService->runAllChecks($customer);
                }
            } catch (\Exception $e) {
                Log::error("Falha ao executar KYT para cliente {$id}: " . $e->getMessage());
            }
        }

        Log::info("Execução KYT concluída para todos os clientes importados.");
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}