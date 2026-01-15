<?php

namespace App\Repositories\Alert\UserGrupoAlert;

use App\Models\Alert\UserGrupoAlert\UserGrupoAlert;
use App\Repositories\AbstractRepository;
use App\Services\Alert\GrupoAlertEmails\GrupoAlertEmailsService;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Illuminate\Support\Facades\DB;

class UserGrupoAlertRepository extends AbstractRepository
{
    public $user;
    public $logService;
public $grupoAlertEmailsService;
    
    public function __construct(UserGrupoAlert $model, UserService $user, LogService $logService, GrupoAlertEmailsService $grupoAlertEmailsService)
    {
        $this->user = $user;
        $this->logService = $logService;
          $this->grupoAlertEmailsService = $grupoAlertEmailsService;
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

            $userId  = $item['user_id'];
            $grup_alert_id  = $item['grup_alert_id'];
            $user = $this->user->show($userId)->first();
            $grupoAlertEmailsService = $this->grupoAlertEmailsService->show(  $grup_alert_id )->first();
            $this->logService->storeLog(
                level: 'info',
                typeAction: 'USER_ASSIGNED_TO_ALERT',
                type: 'ALERT',
                module: 'AlertUser',
                idEntity: $userId,

customMessage: sprintf(
    "Usuário %s foi adicionado ao grupo '%s'",
    $user->first_name ?? 'N/D',
    $grupoAlertEmailsService->name
)

                
            );
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



    public function update(array $data, int $id)
   
{
    $now = now();

    // Se não vierem dados, não faz update
    if (empty($data)) {
        return;
    }

    // Normaliza os dados
    $pairs = collect($data)->map(fn ($item) => [
        'grup_alert_id' => (int) $item['grup_alert_id'],
        'user_id'       => (int) $item['user_id'],
    ]);

    // Todos pertencem ao mesmo grupo
    $grupoId = $pairs->first()['grup_alert_id'];

    DB::transaction(function () use ($pairs, $grupoId, $now) {

        // 1️⃣ Cria ou atualiza os registros enviados
        foreach ($pairs as $item) {

            $this->model->updateOrCreate(
                [
                    'grup_alert_id' => $item['grup_alert_id'],
                    'user_id'       => $item['user_id'],
                ],
                [
                    'updated_at' => $now,
                    'deleted_at' => null, // restaura se estiver soft deleted
                ]
            );
        }

        // 2️⃣ Remove os usuários que NÃO vieram no payload
        $userIds = $pairs->pluck('user_id')->toArray();

        $this->model
            ->where('grup_alert_id', $grupoId)
            ->whereNotIn('user_id', $userIds)
            ->delete();
    });
}




}
