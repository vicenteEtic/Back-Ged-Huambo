<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportClientesJob;

class ImportClientesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clientes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa clientes do arquivo clientes.json e registra na base de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('clientes.json');

        if (!file_exists($filePath)) {
            $this->error("Arquivo clientes.json não encontrado na raiz do projeto!");
            return 1; // código de erro
        }

        // Dispara o Job
        ImportClientesJob::dispatch($filePath);

        $this->info('Job de importação de clientes disparado com sucesso!');
        return 0; // sucesso
    }
}