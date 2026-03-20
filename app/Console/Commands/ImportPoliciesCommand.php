<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportPoliciesJob;

class ImportPoliciesCommand extends Command
{
    /**
     * O nome e assinatura do comando Artisan.
     */
    protected $signature = 'import:policies';

    /**
     * A descrição do comando.
     */
    protected $description = 'Dispara o job ImportPoliciesJob para processar os CSVs de apólices';

    /**
     * Execute o comando.
     */
    public function handle()
    {
        $this->info('Iniciando importação de apólices...');

        // Dispara o job para a fila
        ImportPoliciesJob::dispatch();

        $this->info('Job ImportPoliciesJob disparado com sucesso!');
    }
}