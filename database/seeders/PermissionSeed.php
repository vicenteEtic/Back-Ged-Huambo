<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Permission\Permission;
use App\Models\Permission\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class PermissionSeed extends Seeder
{
    public function run(): void
    {
        $roleAdministrador = Role::updateOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Administrador do sistema',
                'is_active' => true,
            ]
        );

        $roleDirector = Role::updateOrCreate(
            ['name' => 'Director RH'],
            [
                'description' => 'Director de Recursos Humanos',
                'is_active' => true,
            ]
        );

        $roleTecnico = Role::updateOrCreate(
            ['name' => 'Técnico RH'],
            [
                'description' => 'Técnico de Recursos Humanos',
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
            ['name' => 'RH Categorias', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Funcionários', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Documentos', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Tipos de Documento', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Férias', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Feriados', 'operations' => ['show', 'create', 'edit', 'delete']],
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
            ['name' => 'RH Declarações', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Valores em Atraso', 'operations' => ['show', 'create', 'edit', 'delete']],

            // Áreas e Permissões
            ['name' => 'RH Áreas', 'operations' => ['show', 'create', 'edit', 'delete']],
            ['name' => 'RH Permissões Departamento', 'operations' => ['show', 'create', 'delete']],

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

        // Legado: permissões de áreas sem prefixo RH (módulo renomeado para "RH Áreas")
        Permission::where('name', 'like', 'areas-%')->get()->each(function ($p) {
            $p->roles()->detach();
            $p->delete();
            echo "Permissão legada {$p->name} removida.\n";
        });

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $permissionName = Helper::formatarString($module['name'])."-$operation";
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

        $roleAdministrador->permissions()->sync($permissionIds);
        echo "Permissões associadas ao papel {$roleAdministrador->name}.\n";

        $rhModules = array_filter($modules, fn ($m) => str_starts_with($m['name'], 'RH'));
        $rhPermissionIds = [];

        foreach ($rhModules as $module) {
            foreach ($module['operations'] as $operation) {
                $permissionName = Helper::formatarString($module['name'])."-$operation";
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $rhPermissionIds[] = $permission->id;
                }
            }
        }

        $roleDirector->permissions()->sync($rhPermissionIds);
        echo "Permissões RH associadas ao papel {$roleDirector->name}.\n";

        $technicianPermissionIds = [];

        foreach ($rhModules as $module) {
            foreach ($module['operations'] as $operation) {
                if (in_array($operation, ['show', 'create'])) {
                    $permissionName = Helper::formatarString($module['name'])."-$operation";
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission) {
                        $technicianPermissionIds[] = $permission->id;
                    }
                }
            }
        }

        $roleTecnico->permissions()->sync($technicianPermissionIds);
        echo "Permissões RH associadas ao papel {$roleTecnico->name} (show + create).\n";

        User::updateOrCreate(
            ['email' => 'vicentemanueleduardo@gmail.com'],
            [
                'first_name' => 'Administrador',
                'last_name' => 'Sistema',
                'phone' => '11999999999',
                'email' => 'vicentemanueleduardo@gmail.com',
                'password' => bcrypt('12345678'),
                'role_id' => $roleAdministrador->id,
                'is_active' => true,
            ]
        );

        echo "Usuário administrador criado ou atualizado.\n";
    }
}
