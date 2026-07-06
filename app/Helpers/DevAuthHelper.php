<?php

namespace App\Helpers;

use App\Models\User\User;
use App\Models\Permission\Role;
use Illuminate\Support\Facades\Hash;

class DevAuthHelper
{
    /**
     * Garante a existência de um usuário, associa um Role real e retorna o token.
     * * @param string $email
     * @param string $roleName Nome do Role (ex: 'Administrador' ou 'Provedor')
     * @param array $attributes Atributos extras para o User
     * @return array
     */
    public static function bypassAndAuth(string $email = 'admin@gmail.com', string $roleName = 'Administrador', array $attributes = []): array
    {
        // 1. Busca o Role dinamicamente para evitar erro de FK (role_id)
        $role = Role::where('name', $roleName)->first();
        
        // Fallback caso o seeder não tenha sido rodado
        $roleId = $role ? $role->id : 1;

        // 2. Busca ou cria o usuário com tratamento de atributos
        $user = User::updateOrCreate(
            ['email' => $email],
            array_merge([
                'first_name' => 'Dev',
                'last_name'  => 'User',
                'phone'      => '11999999999',
                'role_id'    => $roleId,
                'is_active'  => true,
                'password'   => Hash::make('password'),
                'email_verified_at' => now(),
            ], $attributes)
        );

        // 3. Gerenciamento de Token
        // Deletar tokens anteriores garante que a lista não cresça infinitamente durante testes
        $user->tokens()->where('name', 'DevToken')->delete();
        $token = $user->createToken('DevToken')->plainTextToken;

        return [
            'status'        => 'success',
            'message'       => "Autenticado como {$roleName}",
            'user'          => $user->load('role'), // Carrega o relacionamento para conferência
            'token'         => $token,
            'token_type'    => 'Bearer',
            'credentials'   => [
                'email'    => $email,
                'password' => 'password' // Facilita o login manual no Front-end
            ]
        ];
    }
}