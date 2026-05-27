<?php

namespace App\Repositories\Alert\AlertUser;

use App\Models\Alert\AlertUser\AlertUser;
use App\Repositories\AbstractRepository;
use App\Repositories\Alert\AlertRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlertUserRepository extends AbstractRepository
{
    public UserService $user;
    public LogService $logService;

    public const STATUS_CLOSED = 0;
    public const STATUS_NEW = 1;
    public const STATUS_VALIDATION = 2;
    public const STATUS_SUPERVISION = 3;

    protected AlertRepository $alert;

    public function __construct(
        AlertUser $model,
        UserService $user,
        LogService $logService
    ) {
        parent::__construct($model);

        $this->user = $user;
        $this->logService = $logService;
    }

    public function setAlertRepository(AlertRepository $alert)
    {
        $this->alert = $alert;
    }

    // ============================================
    // COUNT BASE SIMPLES (pivot)
    // ============================================
    public function countAlertUser(int $userId, int $status): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_read', $status)
            ->count();
    }

    // ============================================
    // COUNT COM FILTRO DE DATA (CORRIGIDO)
    // ============================================
    public function countAlertsByUserGrouped(
        int $userId,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): array {
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

        $base = clone $query;

        return [
            'total_active' => (clone $base)
                ->where('a.is_active', self::STATUS_NEW)
                ->count(),

            'closed' => (clone $base)
                ->where('a.is_active', self::STATUS_CLOSED)
                ->count(),

           

            'validation' => (clone $base)
                ->where('a.is_active', self::STATUS_VALIDATION)
                ->count(),

            'supervision' => (clone $base)
                ->where('a.is_active', self::STATUS_SUPERVISION)
                ->count(),
        ];
    }

    // ============================================
    // USERS COM ALERTAS
    // ============================================
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
            return $this->model
                ->distinct()
                ->pluck('user_id');
        }

        return collect([$userArray['id']]);
    }

    // ============================================
    // STORE MANY
    // ============================================
    public function storeMany($data)
    {
        $now = now();

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

        foreach ($data as $item) {
            $user = \App\Models\User::find($item['user_id']);

            $this->logService->storeLog(
                level: 'info',
                typeAction: 'USER_ASSIGNED_TO_ALERT',
                type: 'ALERT',
                module: 'AlertUser',
                idEntity: $item['user_id'],
                alert_id: $item['alert_id'],
                customMessage: 'Usuário atribuído ao alerta'
            );

            if ($user) {
                event(new \App\Events\AlertCreated($user));
            }
        }

        return $inserted;
    }

    // ============================================
    // ALERTAS DO USER LOGADO
    // ============================================
    public function getActiveAlertsForAuthenticatedUser()
    {
        $alerts = $this->model
            ->where('user_id', auth()->id())
            ->where('is_read', 1)
            ->with('alert:id,name,level')
            ->get(['id', 'alert_id', 'is_read']);

        return [
            'total' => $alerts->count(),
            'alerts' => $alerts,
        ];
    }

    // ============================================
    // UPDATE
    // ============================================
    public function updateAlertUser(array $data, int $id)
    {
        return $this->model::where('alert_id', $id)->update($data);
    }
}