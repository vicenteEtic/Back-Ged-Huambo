<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Permission\Role;
use Illuminate\Database\Seeder;
use App\Models\Permission\Permission;
use App\Models\User\User;

class PermissionSeed extends Seeder
{
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Administrador do sistema',
                'is_active' => true,
            ]
        );

        $modules = [
            // Sistema
            ['name' => 'Usuário', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'Regra', 'operations' => ['show', 'create', 'edit', 'delete']],

            // RH
            ['name' => 'RH Departamentos', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Cargos', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Funcionários', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Documentos', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Férias', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Ponto', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Processamento', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Recrutamento', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Formação', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Desempenho', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Benefícios', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Pedidos Benefícios', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Assistência Médica', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Disciplina', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Histórico Funcional', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Carreira', 'operations' => ['show']],
            ['name' => 'RH Progressão', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Salários', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Reforma', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Portal', 'operations' => ['show']],
            ['name' => 'RH Arquivo', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Relatórios', 'operations' => ['show']],

            // Áreas e Permissões
            ['name' => 'Áreas', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'Permissões Departamento', 'operations' => ['show', 'create', 'delete']],

            // Processos (Gestão de Expediente)
            ['name' => 'Processos', 'operations' => ['show', 'create', 'edit', 'delete', 'dispatch', 'assign', 'validate', 'close']],
        ];

        $operationDescriptions = [
            'show' => 'Permite visualizar',
            'create' => 'Permite criar',
            'edit' => 'Permite editar',
            'delete' => 'Permite excluir',
            'dispatch' => 'Permite encaminhar',
            'assign' => 'Permite atribuir',
            'validate' => 'Permite validar',
            'close' => 'Permite encerrar',
        ];

        $permissionIds = [];

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $permissionName = Helper::formatarString($module['name']) . "-$operation";
                $permission = Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'name' => $permissionName,
                        'description' => "{$operationDescriptions[$operation]} {$module['name']}",
                        'is_active' => true,
                    ]
                );
                $permissionIds[] = $permission->id;
                echo "Permissão {$permission->name} criada ou atualizada.\n";
            }
        }

        $role->permissions()->sync($permissionIds);

        echo "Permissões associadas ao papel {$role->name}.\n";

        User::updateOrCreate(
            ['email' => 'vicentemanueleduardo@gmail.com'],
            [
                'first_name' => 'Administrador',
                'last_name' => 'Sistema',
                'phone' => '11999999999',
                'email' => 'vicentemanueleduardo@gmail.com',
                'password' => bcrypt('12345678'),
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );

        echo "Usuário administrador criado ou atualizado.\n";
    }
}
