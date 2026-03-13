<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportClientesJob;
use App\Jobs\ImportPoliciesJob;
use Illuminate\Support\Facades\Bus;

class ImportDataCommand extends Command
{
    protected $signature = 'import:data {--sync : Executa os jobs de forma síncrona}';
    protected $description = 'Executa os jobs ImportClientesJob e ImportPoliciesJob';

    public function handle()
    {
        $this->info("Iniciando importação de clientes e apólices...");

        $sync = $this->option('sync');

        if ($sync) {
            $this->info("Executando jobs de forma síncrona...");

            ImportClientesJob::dispatchSync();
            $this->info("Clientes importados com sucesso.");

            ImportPoliciesJob::dispatchSync();
            $this->info("Polices importadas com sucesso.");
        } else {
            $this->info("Executando jobs via fila...");

            $batch = Bus::batch([
                new ImportClientesJob(),
                new ImportPoliciesJob(),
            ])->name('Importação de Clientes e Polices')->dispatch();

            $this->info("Batch criado com ID: {$batch->id}");
        }

        $this->info("Processo concluído.");
    }
}