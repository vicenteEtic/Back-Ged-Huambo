<?php
namespace App\Repositories\Alert\UserGrupoAlert;

use App\Models\Alert\UserGrupoAlert\UserGrupoAlert;
use App\Repositories\AbstractRepository;
use Illuminate\Support\Facades\DB;

class UserGrupoAlertRepository extends AbstractRepository
{
    public function __construct(UserGrupoAlert $model)
    {
        parent::__construct($model);
    }



public function storeMany($data)
{
    $now = now();

    // Se não vierem dados, não faz nada
    if (empty($data)) {
        return;
    }

    // Normaliza os dados recebidos
    $pairs = collect($data)->map(fn($item) => [
        'grup_alert_id' => (int) $item['grup_alert_id'],
        'user_id'       => (int) $item['user_id'],
    ]);

    // Pega o id do grupo (todos são iguais)
    $grupoId = $pairs->first()['grup_alert_id'];

    // 1️⃣ Cria ou atualiza os registros enviados
    foreach ($pairs as $item) {
        $this->model->updateOrCreate(
            [
                'grup_alert_id' => $item['grup_alert_id'],
                'user_id'       => $item['user_id'],
            ],
            [
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );
    }

    // 2️⃣ Deleta todos os registros desse grupo que não foram enviados
    $userIds = $pairs->pluck('user_id')->implode(',');

    DB::statement("
        DELETE FROM user_grupo_alert
        WHERE grup_alert_id = ?
        AND user_id NOT IN ($userIds)
    ", [$grupoId]);
}
}