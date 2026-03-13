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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;

    public $tries = 3;         // Tentativas em caso de falha
    public $timeout = 3600;    // 1 hora
    public $backoff = 60;      // Espera 1 minuto antes de re-tentar

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
            $header = array_map('trim', $header);

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (empty(array_filter($row))) continue;

                $dataImport = [];

                foreach ($header as $idx => $column) {
                    $value = $row[$idx] ?? null;

                    switch ($column) {
                        case 'Numero_Cliente':
                            $dataImport['contract_number'] = $value;
                            $customerIds[] = $value;
                            break;
                        case 'Codigo_Ramo':
                            $dataImport['branch_code'] = $value;
                            break;
                        case 'Descricao_Ramo':
                            $dataImport['branch_desc'] = $value;
                            break;
                        case 'Codigo_Produto':
                            $dataImport['product_code'] = $value;
                            break;
                        case 'Descricao_Produto':
                            $dataImport['product_desc'] = $value;
                            break;
                        case 'Codigo_Canal':
                            $dataImport['channel_code'] = $value;
                            break;
                        case 'Descricao_Canal':
                            $dataImport['channel_desc'] = $value;
                            break;
                        case 'Codigo_Agente':
                            $dataImport['agent_code'] = $value;
                            break;
                        case 'Descricao_Agente':
                            $dataImport['agent_desc'] = $value;
                            break;
                        case 'Estado_Apolice':
                            $dataImport['status'] = $value;
                            break;
                        case 'Data_Inicio':
                            $dataImport['start_date'] = $this->parseDate($value);
                            break;
                        case 'Data_Fim':
                            $dataImport['end_date'] = $this->parseDate($value);
                            break;
                        case 'Data_Proxima_Renovacao':
                            $dataImport['renewal_date'] = $this->parseDate($value);
                            break;
                        case 'Data_Proximo_Vencimento':
                            $dataImport['issue_date'] = $this->parseDate($value);
                            break;
                        case 'Moeda':
                            $dataImport['moeda'] = $value;
                            break;
                        case 'Capital':
                            $dataImport['capital'] = floatval($value ?? 0);
                            break;
                        case 'Capital_Liquido_Cosseguro':
                            $dataImport['capital_liquido_cosseguro'] = floatval($value ?? 0);
                            break;
                        case 'Premio_Simples':
                            $dataImport['premium_simple'] = floatval($value ?? 0);
                            break;
                        case 'Premio_Total':
                            $dataImport['premium_total'] = floatval($value ?? 0);
                            break;
                        case 'Encargos':
                            $dataImport['charges'] = floatval($value ?? 0);
                            break;
                        case 'Outros_Encargos':
                            $dataImport['outros_encargos'] = floatval($value ?? 0);
                            break;
                        case 'Juros':
                            $dataImport['interest'] = floatval($value ?? 0);
                            break;
                    }
                }

                if (empty($dataImport['contract_number'])) {
                    Log::warning("Linha ignorada: Numero_Apolice vazio");
                    continue;
                }

                try {
                    $this->policiesService->storeOrUpdate(
                        ['contract_number' => $dataImport['contract_number']],
                        $dataImport
                    );

                    $totalInserted++;

                    // Limpeza de memória a cada 1000 registros
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

        // 🔹 Executa KYT apenas para clientes importados
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