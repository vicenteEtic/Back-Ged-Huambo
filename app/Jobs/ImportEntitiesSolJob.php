<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;

class ImportEntitiesSolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;

    public function __construct()
    {
        $this->filePath = database_path('seeders/csv/Sol_Apolices_Emitidas.csv');
    }

    public function handle(): void
    {
        Log::info("=== [INÍCIO] ImportEntitiesJob iniciado ===");

        if (!file_exists($this->filePath)) {
            Log::error("ImportEntitiesJob: [ERRO CRÍTICO] Arquivo não encontrado no caminho especificado: {$this->filePath}");
            return;
        }

        if (($handle = fopen($this->filePath, 'r')) === false) {
            Log::error("ImportEntitiesJob: [ERRO CRÍTICO] Falha de permissão ou trava do sistema ao abrir o arquivo: {$this->filePath}");
            return;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $header = null;
        $lineCounter = 0;

        Log::info("ImportEntitiesJob: Lendo arquivo CSV e processando dados...");

        while (($row = fgetcsv($handle, 0, ";")) !== false) {
            $lineCounter++;

            // Pula as primeiras 6 linhas do arquivo (metadados estruturais do relatório)
            if ($lineCounter <= 6) {
                continue;
            }

            // Limpa espaços e caracteres invisíveis de todas as células da linha
            $row = array_map(function ($cell) {
                $cell = trim($cell);
                return preg_replace('/[\x00-\x1F\x7F]/u', '', $cell);
            }, $row);

            // Ignora linhas completamente vazias de dados
            if (count(array_filter($row)) === 0) {
                $skipped++;
                continue;
            }

            // Detecta e isola a linha de cabeçalho real (Deve ser a Linha 7 do arquivo)
            if (!$header) {
                $header = array_map(function ($h) {
                    $h = trim($h);
                    return preg_replace('/^\x{FEFF}/u', '', $h); // Remove eventual caractere BOM oculto
                }, $row);

                Log::info("ImportEntitiesJob: [SUCESSO] Cabeçalho validado. Colunas: " . implode(" | ", $header));
                continue;
            }

            // Combina as colunas do cabeçalho com os valores da linha atual
            $data = @array_combine($header, $row);
            if (!$data) {
                $skipped++;
                continue;
            }

            // Extração de dados com base na estrutura enviada
            $nomeTomador = $data['Tomador'] ?? null;
            $numeroApolice = $data['Contrato'] ?? null;
            
            // Tratamento dinâmico para identificação do cliente (customer_number)
            $telefoneContato = !empty($data['Telemóvel']) ? $data['Telemóvel'] : ($data['Telefone'] ?? null);
            if (!empty($telefoneContato)) {
                $customerNumber = preg_replace('/[^0-9]/', '', $telefoneContato);
            } else {
                $customerNumber = substr(md5($nomeTomador), 0, 9);
            }

            // Ignora caso a coluna essencial do Tomador venha sem dados estruturados
            if (empty($nomeTomador)) {
                $skipped++;
                continue;
            }

            try {
                // Descobre se o registro vai ser inserido do zero ou apenas atualizado no banco
                $exists = Entities::where('customer_number', $customerNumber)->exists();

                Entities::updateOrCreate(
                    ['customer_number' => $customerNumber],
                    [
                        'social_denomination' => $nomeTomador,
                        'policy_number'       => $numeroApolice,
                        'entity_type'         => 1, 
                        'nif'                 => $data['NIF'] ?? $customerNumber, 
                    ]
                );

                if ($exists) {
                    $updated++;
                } else {
                    $imported++;
                }

                // Log consolidado a cada lote de 1000 iterações para monitoramento em tempo real
                $totalProcessado = $imported + $updated;
                if ($totalProcessado % 1000 === 0) {
                    Log::info("ImportEntitiesJob: [PROGRESSO] {$totalProcessado} registros já foram processados no banco de dados.");
                }

            } catch (\Throwable $e) {
                Log::error("ImportEntitiesJob: [FALHA DE PERSISTÊNCIA] Erro na Linha #{$lineCounter}: {$e->getMessage()}");
            }
        }

        fclose($handle);

        Log::info("=== [FINALIZADO] Sincronização do arquivo concluída ===");
        Log::info("Resultados Finais: Linhas totais: {$lineCounter} | Inseridos: {$imported} | Atualizados: {$updated} | Pulados: {$skipped}");
    }
}