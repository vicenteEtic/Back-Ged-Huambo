<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportClientesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 3600;

    protected string $csvPath;
    protected int $chunkSize;

    public function __construct(string $csvPath = null, int $chunkSize = 5000)
    {
        $this->csvPath = $csvPath ?? base_path('clientes.csv');
        $this->chunkSize = $chunkSize;
    }

    public function handle()
    {
        if (!file_exists($this->csvPath)) {
            Log::error("Arquivo CSV não encontrado: {$this->csvPath}");
            return;
        }

        Log::info("Iniciando leitura do CSV: {$this->csvPath}");

        $handle = fopen($this->csvPath, 'r');
        if ($handle === false) {
            Log::error("Não foi possível abrir o arquivo CSV.");
            return;
        }

        $header = fgetcsv($handle, 0, ',');
        $header = array_map('trim', $header);

        $rows = [];
        $totalDispatched = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (empty(array_filter($row))) continue;
            $rows[] = $row;

            if (count($rows) >= $this->chunkSize) {
                ImportClientesChunkJob::dispatch($rows, $header);
                $totalDispatched++;
                $rows = [];
            }
        }

        // último chunk
        if (!empty($rows)) {
            ImportClientesChunkJob::dispatch($rows, $header);
            $totalDispatched++;
        }

        fclose($handle);

        Log::info("Importação CSV dividida em {$totalDispatched} chunks e enviada para processamento.");
    }
}