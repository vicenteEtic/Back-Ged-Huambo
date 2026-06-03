<?php

namespace App\Console\Commands;

use App\Jobs\BeneficiariosStagingJob;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use App\Jobs\ImportPoliciesJob;
use App\Jobs\DispatchCustomerJobsJob;
use App\Jobs\ImportApolAnuladaEstornoJob;
use App\Jobs\ImportPolicyChangesJob;
use App\Jobs\ImportRecibosCobradosJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ImportAndDispatchPolicies extends Command
{
    protected $signature = 'policies:import-dispatch';
    protected $description = 'Importa CSV de apólices e dispara jobs por cliente';

    public function handle()
    {
        $this->info('🚀 Iniciando pipeline de importação KYT...');

        /**
         * 🏗️ FASE 1: Importação dos CSVs para as staging tables
         *      (descomentar quando os imports estiverem prontos)
         */
        $importJobs = [];
        // $importJobs[] = new ImportPoliciesJob();
        // $importJobs[] = new ImportApolAnuladaEstornoJob();
        // $importJobs[] = new ImportRecibosCobradosJob();
        // $importJobs[] = new BeneficiariosStagingJob();
        // $importJobs[] = new ImportPolicyChangesJob();

        if (!empty($importJobs)) {
            /**
             * Se há imports, dispara-os como batch e encadeia o dispatch
             * ONLY depois de todos os imports terminarem.
             */
            Bus::batch($importJobs)
                ->onQueue('import')
                ->allowFailures()
                ->finally(function (Batch $batch) {
                    Log::info("📥 Importação concluída. Iniciando dispatch...");
                    DispatchCustomerJobsJob::dispatch()->onQueue('cliente');
                })
                ->dispatch();

            $this->info("📦 " . count($importJobs) . " job(s) de importação em batch.");
        } else {
            /**
             * Sem imports → segue direto para o dispatch
             */
            DispatchCustomerJobsJob::dispatch()->onQueue('cliente');
            $this->info('📬 DispatchCustomerJobsJob enfileirado na queue cliente.');
        }
    }
}