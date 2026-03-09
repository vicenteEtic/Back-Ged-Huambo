<?php

namespace App\Repositories\Alert\AlertUser;

use App\Models\Alert\AlertUser\AlertUser;
use App\Repositories\AbstractRepository;
use App\Repositories\Alert\AlertRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;



class AlertUserRepository extends AbstractRepository
{

    public $user;
    public $logService;
    public const STATUS_CLOSED = 0;
    public const STATUS_NEW = 1;
    public const STATUS_VALIDATION = 2;
    public const STATUS_SUPERVISION = 3;
    public $alert;

    public function __construct(AlertUser $model, UserService $user, LogService $logService, AlertRepository $alert)
    {

        $this->user = $user;
           $this->alert = $alert;
        $this->logService = $logService;
        parent::__construct($model);
    }


    public function countAlertUser(int $userId, int $status): int
    {
         return $this->model
        ->where('user_id', $userId)
        ->where('is_read', $status)
        ->count();
    }

  
    public function countAlertsByUserGrouped(int $userId): array
    {
        return [
            'total_active' => $this->countAlertUser($userId,self::STATUS_NEW),
            'closed'       =>  $this->countAlertUser($userId,self::STATUS_CLOSED),
            'new'          =>  $this->countAlertUser($userId,self::STATUS_NEW),
            'validation'   =>  $this->countAlertUser($userId,self::STATUS_VALIDATION),
            'supervision'  =>  $this->countAlertUser($userId,self::STATUS_SUPERVISION),
        ];
    }

public function getUsersWithAlerts()
{
    $user = $this->user->me();
    $userArray = json_decode(json_encode($user), true);

    $permissionName = "compliance-officer-show";
    $permissionFound = null;

    if (isset($userArray['role']['permissions'])) {

        foreach ($userArray['role']['permissions'] as $permission) {

            if ($permission['name'] === $permissionName) {
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

    return $users;
}


    public function storeMany($data)
    {
        $now = now();

        // insere na pivot alert_user
        $inserted = $this->model->insert(
            collect($data)->map(function ($item) use ($now) {
                $alertData = $this->alert->findByValidate($item['alert_id']);
                return array_merge($item, [
                    'is_read' =>   $alertData->is_active,
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
                    'Usuário %s ( foi adicionado ao alerta ',
                    $user->first_name ?? 'N/D',
                    $userId

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
            ->whereHas('alert', fn($q) => $q->where('is_read', 1))
            ->with('alert:id,name,is_read,level')
            ->get(['id', 'alert_id', 'is_read']);

        $data = [
            'total'  => $alerts->count(),
            'alerts' => $alerts,
        ];
        return $data;
    }

    public function updateAlertUser(array $data, int $id)
    {
        $affected = $this->model::where('alert_id', $id)->update($data);

        return $affected;
    }
}
