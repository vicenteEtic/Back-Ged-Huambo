<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportPoliciesJob;
use App\Jobs\DispatchCustomerJobsJob;
use App\Jobs\ImportApolAnuladaEstornoJob;
use App\Jobs\ImportPolicyChangesJob;
use Illuminate\Support\Facades\Log;

class ImportAndDispatchPolicies extends Command
{
    protected $signature = 'policies:import-dispatch';
    protected $description = 'Importa CSV de apólices e dispara jobs por cliente';

    public function handle()
    {
        $this->info('🚀 Iniciando importação de apólices...');

        // 1️⃣ Dispara job de importação do CSV
     // ImportPoliciesJob::dispatch()->onQueue('policy')->delay(now());
      ImportApolAnuladaEstornoJob::dispatch()->onQueue('policy')->delay(now());

    
        $this->info('📄 Job de importação disparado. Aguarde a conclusão...');


      //  ImportPolicyChangesJob::dispatch()->onQueue('policyChanges')->delay(now());
        // 2️⃣ Dispara job que vai processar todos os clientes (pode colocar delay para garantir que o Import finalize)
              // 2️⃣ Dispara job de dispatch de clientes na fila "default", com delay para garantir que o import finalize
          //  DispatchCustomerJobsJob::dispatch()->onQueue('import')->delay(now());

        $this->info('📬 Job de dispatch de clientes programado para rodar em 1 minuto.');
    }
}