<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use App\Services\KYT\CustomerKYTDataMocker;

class TestKYTEngine extends Command
{
    protected $signature = 'kyt:test-random';
    protected $description = 'Injeta dados simulados completos para validar as 10 regras do motor KYT em 10 clientes aleatórios';

    public function handle(CustomerKYTService $kytService)
    {
        // 1. Procura 10 entidades aleatórias no banco de dados
        $customers = Entities::inRandomOrder()->limit(10)->get();

        if ($customers->isEmpty()) {
            $this->error('❌ Nenhuma entidade (customer) encontrada na tabela de entities para realizar o teste.');
            return;
        }

        $this->info("🎲 Selecionados " . $customers->count() . " clientes aleatórios para o teste global de KYT.\n");

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $index => $customer) {
            $this->newLine(2);
            $this->line("=================================================================");
            $this->info("👤 [#" . ($index + 1) . "] Processando Cliente: {$customer->customer_number} | {$customer->social_denomination}");
            $this->line("=================================================================");

            // Lista de todos os cenários disponíveis no Mocker para execução sequencial
            $scenarios = [
                'Regra 1: Aumento de Capital'             => 'scenarioHighCapitalIncrease',
                'Regra 2: Resgate Antecipado'             => 'scenarioEarlyRedemption',
                'Regra 3: Prémio Elevado (Ratio >= 8%)'   => 'scenarioHighPremium',
                'Regra 4: Fracionamento (Smurfing)'       => 'scenarioSmurfing',
                'Regra 5: Quebra de Histórico (Churning)' => 'scenarioPolicyChurning',
                'Regra 6: Substituição Rápida (<= 7 dias)'=> 'scenarioRapidReplacement',
                'Regra 7: Pagamento por Terceiros'        => 'scenarioThirdPartyPayments',
                'Regra 8: Troca de Beneficiários'         => 'scenarioFrequentBeneficiaryChanges',
                'Regra 9: Geografia de Alto Risco'        => 'scenarioHighRiskGeography',
                'Regra 10: Sobrepagamento com Reembolso'  => 'scenarioOverpayment',
            ];

            foreach ($scenarios as $label => $method) {
                try {
                    // Obtém a massa de dados específica do cenário
                    $mockData = CustomerKYTDataMocker::$method($customer);

                    // Garante que chaves opcionais existam para evitar "undefined array key"
                    $policies  = $mockData['policies'] ?? [];
                    $changes   = $mockData['changes'] ?? [];
                    $refunds   = $mockData['refunds'] ?? [];
                    $receipts  = $mockData['receipts'] ?? [];
                    $benefic   = $mockData['beneficiaries'] ?? [];

                    // No caso da Regra 9 (Geografia), se o seu service ler beneficiários de forma separada, 
                    // certifique-se de que a assinatura ou o array mapeado os processe.
                    
                    $this->line("   ↳ Executando {$label}...");

                    // Dispara o motor de regras injetando a massa mockada diretamente na memória
                    $kytService->runAllChecksMemory(
                        $customer,
                        $policies,
                        $changes,
                        $refunds,
                        $receipts
                    );

                } catch (\Throwable $e) {
                    $this->error("   ❌ Erro ao executar {$label}: " . $e->getMessage());
                }
            }

            // Exibe o relatório de alertas que foram gerados/atualizados nesta rodada
            $this->triggeredAlertsInfo($customer);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("🏁 Teste de estresse em lote aleatório finalizado com sucesso!");
    }

    private function triggeredAlertsInfo($customer)
    {
        // Captura os alertas gerados nos últimos 2 minutos para este cliente específico
        $alerts = \App\Models\Alert\Alert::where('entity_id', $customer->id)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->get();

        if ($alerts->isEmpty()) {
            $this->line("\n   ℹ️  Nenhum alerta novo gerado para este perfil (verifique os critérios de corte/pontuação).");
            return;
        }

        $this->line("\n   🚨  Alertas Disparados no Banco para este Cliente:");
        foreach ($alerts as $alert) {
            // Define uma cor visual baseada no nível de criticidade
            $color = match (strtolower($alert->level)) {
                'alto', 'high', 'crítico' => 'error',
                'médio', 'medium'        => 'comment',
                default                  => 'info',
            };

            $this->{$color}("      - [{$alert->level}] Tipo: {$alert->type} | Score: {$alert->score}");
            $this->line("        Descrição: " . substr($alert->description, 0, 90) . "...");
        }
        $this->newLine();
    }
}