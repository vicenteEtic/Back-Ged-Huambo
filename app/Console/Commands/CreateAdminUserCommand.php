<?php

namespace App\Console\Commands;

use App\Models\User\User;
use App\Models\Permission\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'user:create-interactive'; // Alterei o nome para refletir que agora é genérico
    protected $description = 'Cria ou atualiza um usuário selecionando a Role dinamicamente';

    public function handle()
    {
        $this->info('--- Gestão Interativa de Usuários ---');

        // 1. Obter Roles em tempo real
        $roles = Role::all(['id', 'name']);

        if ($roles->isEmpty()) {
            $this->error('Nenhuma Role encontrada no banco de dados. Rode os seeders primeiro.');
            return 1;
        }

        // Criamos um array para o menu de escolha: ['id' => 'Nome da Role']
        $roleOptions = $roles->pluck('name', 'id')->toArray();

        // 2. Coleta de dados básicos
        $fullName = $this->ask('Nome completo');
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0];
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Usuário';

        $email = $this->ask('E-mail');

        // 3. Telefone Aleatório ou Manual
        $phone = $this->ask('Telefone (9XXXXXXXX) ou vazio para aleatório');
        if (empty($phone)) {
            $phone = '9' . rand(10000000, 99999999);
        }

        // 4. Seleção da Role (Interface Dinâmica)
        // O terceiro parâmetro é o índice padrão (opcional)
        $selectedRoleName = $this->choice('Selecione a Role do usuário', array_values($roleOptions));

        // Descobrimos o ID baseado no nome escolhido
        $roleId = array_search($selectedRoleName, $roleOptions);

        // 5. Senha com Confirmação (Secret)
        $password = $this->ask('Digite a senha');
        $passwordConfirmation = $this->ask('Confirme a senha');

        if ($password !== $passwordConfirmation) {
            $this->error('As senhas não coincidem!');
            return 1;
        }

        // 6. Persistência dos dados
        try {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'phone'      => $phone,
                    'password'   => Hash::make($password),
                    'role_id'    => $roleId,
                    'is_active'  => true,
                ]
            );

            $this->info("\n✅ Usuário [{$user->email}] processado com sucesso!");

            $apiToken = 'N/A (Apenas em Local/Dev)';

            // Gera o token apenas se estiver em ambiente local ou testing
            if (App::environment(['local', 'development', 'testing'])) {
                // Certifique-se que o Model User usa o trait HasApiTokens do Sanctum
                $user->tokens()->delete(); // Limpa tokens anteriores para evitar lixo
                $apiToken = $user->createToken('dev-access-token')->plainTextToken;
            }

            // 4. Exibição dos resultados
            $tableData = [
                ['Nome', $firstName . ' ' . $lastName],
                ['Email', $email],
                ['Telefone', $phone],
                ['Role', "$selectedRoleName (ID: $roleId)"],
                ['Ambiente', App::environment()],
            ];

            // Adiciona o Token na tabela se ele foi gerado
            if ($apiToken !== 'N/A (Apenas em Local/Dev)') {
                $tableData[] = ['API Token', $apiToken];
                $this->warn("\n⚠️  Cuidado: O token acima é exibido apenas uma vez!");
            }

            $this->table(['Campo', 'Valor'], $tableData);
        } catch (\Exception $e) {
            $this->error('Erro crítico: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
