<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TransationSolJob;
use App\Models\Transation\transaionControl;

class ImportApolicesCsvCommand extends Command
{
    protected $signature = 'import:apolices-csv';
    protected $description = 'Lê o arquivo local de apólices e joga no motor do Job';

    public function handle()
    {
        $filePath = database_path('seeders/csv/Apolices_Emitidas.csv');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado em: {$filePath}");
            return 1;
        }

        $this->info("Lendo e parseando o arquivo CSV...");

        $csvData = [];
        $header = null;
        $lineCounter = 0;

        $handle = fopen($filePath, 'r');
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineCounter++;

            if ($lineCounter <= 6) continue;

            $row = array_map('trim', $row);
            if (count(array_filter($row)) === 0) continue;

            if (!$header) {
                $header = $row;
                $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
                continue;
            }

            if (count($header) === count($row)) {
                $csvData[] = array_combine($header, $row);
            }
        }
        fclose($handle);

        // Cria o registro na tabela de logs/controle
        $control = transaionControl::create([
            'user_id' => 1, // ID do sistema/admin
            'total'   => 0,
        ]);

        // Envia os dados para a fila
        TransationSolJob::dispatch($csvData, 1, $control->id);

        $this->info("Sucesso! " . count($csvData) . " registros enviados para a fila (Control ID: {$control->id}).");
        return 0;
    }
}