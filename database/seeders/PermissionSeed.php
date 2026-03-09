<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Permission\Role;
use Illuminate\Database\Seeder;
use App\Models\Permission\Permission;
use App\Models\User\User;

class PermissionSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
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

            [
                'name' => 'Usuário',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Estatística',
                'operations' => ['show']
            ],

            [
                'name' => 'Regra',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Entidades',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Avaliações',
                'operations' => ['show', 'create', 'edit']
            ],
              [
                'name' => 'Importar Avaliações',
                'operations' => ['show', 'create']
            ],

            [
                'name' => 'Canais',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Categorias',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Países',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Riscos de Produtos',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Diligências',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Profissões',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'CAE',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Perfil',
                'operations' => ['show', 'edit']
            ],

            [
                'name' => 'Receptores de Alertas',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Compliance Officer',
                'operations' => ['show']
            ],

            [
                'name' => 'Peps Internos',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Peps Externos',
                'operations' => ['show']
            ],

            [
                'name' => 'Sanções',
                'operations' => ['show']
            ],

            [
                'name' => 'Transações',
                'operations' => ['show', 'create']
            ],

            [
                'name' => 'Capacidades de Identificação',
                'operations' => ['show', 'create', 'edit']
            ],
            [
                'name' => 'Formas Jurídicas',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Histórico Geral',
                'operations' => ['show']
            ],

            [
                'name' => 'Alertas',
                'operations' => ['show']
            ],

            [
                'name' => 'Ponderadores',
                'operations' => ['show', 'edit']
            ],

            [
                'name' => 'Regras & Permissões',
                'operations' => ['show', 'create', 'edit']
            ],

            [
                'name' => 'Perfil Utilizador',
                'operations' => ['show','edit']
            ],

            [
                'name' => 'Painel',
                'operations' => ['show']
            ],

            [
                'name' => 'Estatísticas',
                'operations' => ['show']
            ],

        ];

        $operationDescriptions = [
            'show' => 'Permite listar',
            'create' => 'Permite criar',
            'edit' => 'Permite editar',
            'delete' => 'Permite excluir',
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
            [
                'email' => 'vicentemanueleduardo@gmail.com'
            ],
            [
                'first_name' => 'Administrador',
                'last_name' => 'Sistema',
                'phone' => '11999999999',
                'email' => 'vicentemanueleduardo@gmail.com',
                'password' => bcrypt('12345678'),
                'role_id' => $role->id,
                'is_active' => true
            ]
        );

        echo "Usuário administrador criado ou atualizado.\n";
    }
}
