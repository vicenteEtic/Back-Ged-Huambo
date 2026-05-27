<?php

namespace App\Repositories\Alert\AlertUser;

use App\Models\Alert\AlertUser\AlertUser;
use App\Repositories\AbstractRepository;
use App\Repositories\Alert\AlertRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AlertUserRepository extends AbstractRepository
{

    public $user;
    public $logService;
    public const STATUS_CLOSED = 0;
    public const STATUS_NEW = 1;
    public const STATUS_VALIDATION = 2;
    public const STATUS_SUPERVISION = 3;

    protected AlertRepository $alert;

    public function setAlertRepository(AlertRepository $alert)
    {
        $this->alert = $alert;
    }
    public function __construct(AlertUser $model, UserService $user, LogService $logService)
    {

        $this->user = $user;
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


    public function countAlertsByUserGrouped(int $userId, ?Carbon $start = null, ?Carbon $end = null): array
{
    $query = DB::table('alert_user as au')
        ->join('alert as a', 'a.id', '=', 'au.alert_id')
        ->where('au.user_id', $userId);

    if ($start && $end) {
        $query->whereBetween('a.created_at', [$start, $end]);
    } elseif ($start) {
        $query->where('a.created_at', '>=', $start);
    } elseif ($end) {
        $query->where('a.created_at', '<=', $end);
    }

    return [
        'total_active' => (clone $query)->where('a.is_active', self::STATUS_NEW)->count(),
        'closed'       => (clone $query)->where('a.is_active', self::STATUS_CLOSED)->count(),
        'new'          => (clone $query)->where('a.is_active', self::STATUS_NEW)->count(),
        'validation'   => (clone $query)->where('a.is_active', self::STATUS_VALIDATION)->count(),
        'supervision'  => (clone $query)->where('a.is_active', self::STATUS_SUPERVISION)->count(),
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
                $alertRepo = $this->alert ?? app(AlertRepository::class);
                $alertData = $alertRepo->findByValidate(['id' => $item['alert_id']]);
                return array_merge($item, [
                    'is_read' => $alertData->is_active,
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
            ->where('is_read', 1) // filtro correto
            ->with('alert:id,name,level')
            ->get(['id', 'alert_id', 'is_read']);
    
        return [
            'total'  => $alerts->count(),
            'alerts' => $alerts,
        ];
    }

    public function updateAlertUser(array $data, int $id)
    {
        $affected = $this->model::where('alert_id', $id)->update($data);

        return $affected;
    }
}
