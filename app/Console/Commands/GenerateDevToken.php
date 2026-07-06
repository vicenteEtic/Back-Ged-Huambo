<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\DevAuthHelper;

class GenerateDevToken extends Command
{
    /**
     * Assinatura atualizada: agora aceita o nome da role como opção.
     * Exemplo: php artisan dev:auth admin@teste.com --role=Provedor
     */
    protected $signature = 'dev:auth 
                            {email=admin@gmail.com : O email do usuário para login/criação} 
                            {--role=Administrador : O nome da Role (ex: Administrador, Provedor)}';

    protected $description = 'Gera um token de acesso instantâneo via Sanctum para ambiente de desenvolvimento';

    public function handle()
    {
        // Verifica se estamos em produção para evitar acidentes
        if (app()->isProduction()) {
            $this->error('🛑 Este comando não pode ser executado em ambiente de produção!');
            return 1;
        }

        $email = $this->argument('email');
        $roleName = $this->option('role');

        $this->warn("⚙️  Gerando acesso para: {$email} [Role: {$roleName}]...");

        try {
            // Chamada ajustada para a nova assinatura do Helper
            $result = DevAuthHelper::bypassAndAuth($email, $roleName);

            $user = $result['user'];

            $this->newLine();
            $this->info("✅ Usuário autenticado com sucesso!");
            
            // Tabela com informações úteis para o desenvolvedor
            $this->table(
                ['ID', 'Nome Completo', 'Email', 'Role (Cargo)', 'Status'],
                [[
                    $user->id, 
                    "{$user->first_name} {$user->last_name}", 
                    $user->email, 
                    $user->role->name ?? 'N/A',
                    $user->is_active ? '✅ Ativo' : '❌ Inativo'
                ]]
            );

            $this->newLine();
            $this->info("🔑 Bearer Token Gerado:");
            $this->line($result['token']);
            $this->newLine();
            
            $this->comment("Copiado! Use no Header: [Authorization: Bearer {$result['token']}]");

        } catch (\Exception $e) {
            $this->error("❌ Erro ao processar: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}