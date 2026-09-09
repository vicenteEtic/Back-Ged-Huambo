<?php
namespace App\Repositories\User;

use App\Models\User\User;
use App\Repositories\AbstractRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRepository extends AbstractRepository
{
    protected array $defaultRelations = [
        'role',
        'employee',
        'employee.department',
        'employee.position',
        'employee.careerCategory',
    ];

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = array_unique(array_merge((array) $relationships, $this->defaultRelations));

        return parent::index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function show(int|string $id, array $relationships = [])
    {
        $relationships = array_unique(array_merge($relationships, $this->defaultRelations));

        return parent::show($id, $relationships);
    }

   public function changePassword($data, $id)
{
    $user = $this->model::findOrFail($id);

    $user->password = Hash::make(trim((string) $data['new_password']));
    $user->save(); // Salva no banco

    return $user; // Retorna o objeto usuário atualizado
}

}