<?php
namespace App\Repositories\User;

use App\Models\User\User;
use App\Repositories\AbstractRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRepository extends AbstractRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

   public function changePassword($data, $id)
{
    $user = $this->model::findOrFail($id);

    $user->password = Hash::make(trim((string) $data['new_password']));
    $user->save(); // Salva no banco

    return $user; // Retorna o objeto usuário atualizado
}

}