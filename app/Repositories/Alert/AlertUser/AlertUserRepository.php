<?php

namespace App\Repositories\Alert\AlertUser;

use App\Models\Alert\AlertUser\AlertUser;
use App\Repositories\AbstractRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;

class AlertUserRepository extends AbstractRepository
{

    public $user;
    public $logService;

    public function __construct(AlertUser $model, UserService $user, LogService $logService)
    {

        $this->user = $user;
        $this->logService = $logService;
        parent::__construct($model);
    }

    public function countActiveAlertsByUser($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereHas('alert', function ($q) {
                $q->where('is_active', 1);
            })
            ->count();
    }

    public function countInactiveAlertsByUser($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereHas('alert', function ($q) {
                $q->where('is_active', 0);
            })
            ->count();
    }
    public function getUsersWithAlerts()
    {
        $user = $this->user->me(); // Pega os dados do usuário
        $userArray = json_decode(json_encode($user), true); // Garante array

        $permissionId = 57; // Permite listar Compliance Officer
        $permissionFound = null;

        // Busca a permissão
        if (isset($userArray['role']['permissions'])) {
            foreach ($userArray['role']['permissions'] as $permission) {
                if ($permission['id'] == $permissionId) {
                    $permissionFound = $permission;
                    break;
                }
            }
        }

        if ($permissionFound) {
            // Se tem permissão, pega todos os user_id distintos
            $users = $this->model
                ->distinct()
                ->pluck('user_id');
        } else {
            // Se não tem permissão, pega apenas o user logado
            $users = collect([$userArray['id']]);
        }

        // Retorna a coleção de IDs
        return $users;
    }


    public function storeMany($data)
    {
        $now = now();

        // insere na pivot alert_user
        $inserted = $this->model->insert(
            collect($data)->map(function ($item) use ($now) {
                return array_merge($item, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            })->toArray()
        );

        // dispara evento em tempo real para cada utilizador
        foreach ($data as $item) {
            $alertId = $item['alert_id'];
            $userId  = $item['user_id'];

          $user = $this->user->show($userId)->first();

            $this->logService->storeLog(
                level: 'info',
                typeAction: 'USER_ASSIGNED_TO_ALERT',
                type: 'ALERT',
                module: 'AlertUser',
                idEntity: $userId,
                alert_id: $alertId,
                customMessage: sprintf(
                    'Usuário %s ( foi adicionado ao alerta ID #%d',
                    $user->first_name ?? 'N/D',
                    $userId,
                    $alertId
                )
            );

            $user = \App\Models\User::find($item['user_id']);
            if ($user) {
                event(new \App\Events\AlertCreated($user));
            }
        }

        return $inserted;
    }





    public function getActiveAlertsForAuthenticatedUser()
    {
        $alerts = $this->model
            ->where('user_id', auth()->id())
            ->whereHas('alert', fn($q) => $q->where('is_active', 1))
            ->with('alert:id,name,is_active,level')
            ->get(['id', 'alert_id', 'is_read']);

        $data = [
            'total'  => $alerts->count(),
            'alerts' => $alerts,
        ];
        return $data;
    }
}
