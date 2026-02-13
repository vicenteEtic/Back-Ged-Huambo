<?php

namespace App\Services\Alert\AlertUser;

use App\Models\Alert\Alert;
use App\Models\Alert\AlertUser\AlertUser;
use App\Models\User\User;

use App\Repositories\Alert\AlertUser\AlertUserRepository;
use App\Services\AbstractService;

class AlertUserService extends AbstractService
{
    public function __construct(AlertUserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Resumo de alertas de UM usuário
     */
    public function getUserAlertSummary($userId)
    {
        $active   = $this->repository->countActiveAlertsByUser($userId);
        $inactive = $this->repository->countInactiveAlertsByUser($userId);

        return [
            'user_id'        => $userId,
            'active_alerts'  => $active,
            'inactive_alerts' => $inactive,
        ];
    }

    /**
     * Resumo de alertas de TODOS os usuários relacionados a alertas
     */
   public function getAllUsersAlertSummary()
{
    // Busca todos os usuários que têm alertas associados
    $userIds = $this->repository->getUsersWithAlerts();

    return collect($userIds)->map(function ($userId) {
        $user = \App\Models\User::find($userId);

        // Resumo por status do usuário
        $summary = $this->repository->countAlertsByUserGrouped($userId);

        return [
            'id'              => $user->id,
            'name'            => $user->first_name . ' ' . $user->last_name,
            'email'           => $user->email,
            'active_alerts'   => $summary['total_active'] ?? 0,
            'inactive_alerts' => $summary['closed'] ?? 0,
            'new'             => $summary['new'] ?? 0,
            'validation'      => $summary['validation'] ?? 0,
            'supervision'     => $summary['supervision'] ?? 0,
        ];
    })->values();
}


    /**
     * Retorna usuário com seus alerts
     */
  public function getUserWithAlerts($userId)
{
    $user = User::with(['alerts.entity'])->findOrFail($userId);

    return [
        'id'    => $user->id,
        'name'  => $user->first_name . ' ' . $user->last_name,
        'email' => $user->email,
        'alerts' => $user->alerts->map(function ($alert) {
            return [
                'id'        => $alert->id,
                'type'      => $alert->type,
                'name'      => $alert->name,
                'level'     => $alert->level,
                'is_active' => $alert->is_active,

                // 🔹 ENTIDADE RELACIONADA
                'entity' => $alert->entity ? [
                    'id'                => $alert->entity->id,
                    'social_denomination'=> $alert->entity->social_denomination,
                    'customer_number'   => $alert->entity->customer_number,
                    'policy_number'     => $alert->entity->policy_number,
                ] : null,

                // 🔹 DADOS DO PIVOT
                'pivot' => [
                    'is_read'    => $alert->pivot->is_read,
                    'created_at' => $alert->pivot->created_at,
                ],
            ];
        }),
    ];
}


    public function storeMany(array $data)
{
    return $this->repository->storeMany($data);

}
public function countActiveAlertsForAuthenticatedUser()
{
    return $this->repository->getActiveAlertsForAuthenticatedUser();

}
  public function updateAlertUser( $data,  $id)
    {
  
 return $this->repository->updateAlertUser($data,$id);
 
    }

}