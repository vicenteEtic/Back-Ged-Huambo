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
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;

class ImportPoliciesRootJob implements ShouldQueue
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

        $basePath = base_path(); // raiz do projeto

        // Pega todos os arquivos que começam com "apolices_" na raiz
        $csvFiles = File::glob($basePath . '/apolices_*.csv');

        if (empty($csvFiles)) {
            Log::warning("Nenhum arquivo CSV encontrado na raiz com padrão apolices_*");
            return;
        }

        $totalInserted = 0;
        $customersPolicies = [];

        foreach ($csvFiles as $path) {
            Log::info("Processando arquivo: {$path}");

            if (($handle = fopen($path, 'r')) !== false) {
                $header = fgetcsv($handle, 0, ',');
                if (!$header) {
                    Log::error("CSV vazio ou cabeçalho inválido: {$path}");
                    fclose($handle);
                    continue;
                }
                $header = array_map('trim', $header);

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    if (empty(array_filter($row))) continue;

                    $dataImport = [];
                    foreach ($header as $idx => $column) {
                        $value = $row[$idx] ?? null;
                        $columnLower = strtolower($column);

                        switch ($columnLower) {
                            case 'numero_apolice':
                                $dataImport['contract_number'] = $value;
                                break;
                            case 'numero_cliente':
                                $dataImport['customer_number'] = $value;
                                break;
                            case 'descricao_produto':
                                $dataImport['product_desc'] = strtoupper(trim($value));
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
                            case 'capital':
                                $dataImport['capital'] = is_numeric($value) ? floatval($value) : 0;
                                break;
                            case 'premio_total':
                                $dataImport['premium_total'] = is_numeric($value) ? floatval($value) : 0;
                                break;
                            case 'juros':
                                $dataImport['interest'] = is_numeric($value) ? floatval($value) : 0;
                                break;
                        }
                    }

                    if (empty($dataImport['contract_number']) || empty($dataImport['customer_number'])) {
                        Log::warning("Linha ignorada no arquivo {$path}: Numero_Apolice ou Numero_Cliente vazio");
                        continue;
                    }

                    try {
                        $this->policiesService->storeOrUpdate(
                            ['contract_number' => $dataImport['contract_number']],
                            $dataImport
                        );
                        $totalInserted++;

                        $customerId = $dataImport['customer_number'];
                        $customersPolicies[$customerId][] = [
                            'numero_apolice' => $dataImport['contract_number'],
                            'descricao_produto' => $dataImport['product_desc'] ?? '',
                            'estado_apolice' => $dataImport['status'] ?? null,
                            'data_inicio' => $dataImport['start_date'] ?? null,
                            'data_fim' => $dataImport['end_date'] ?? null,
                            'capital' => $dataImport['capital'] ?? 0,
                            'premium_total' => $dataImport['premium_total'] ?? 0,
                            'interest' => $dataImport['interest'] ?? 0,
                        ];

                        if ($totalInserted % 1000 === 0) gc_collect_cycles();

                    } catch (\Exception $e) {
                        Log::error("Falha ao inserir apólice {$dataImport['contract_number']}: " . $e->getMessage());
                    }
                }

                fclose($handle);
            }
        }

        Log::info("Importação de todas as policies concluída! Total inserido/atualizado: {$totalInserted}");

        // Executa KYT
        foreach ($customersPolicies as $customerId => $policies) {
            try {
                $customer = Entities::where('customer_number', $customerId)->first();
                if ($customer) $this->kytService->runAllChecksMemory($customer, $policies);
            } catch (\Exception $e) {
                Log::error("Falha ao executar KYT para cliente {$customerId}: " . $e->getMessage());
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