<?php

namespace App\Repositories\Alert;

use App\Models\Alert\Alert;
use App\Repositories\AbstractRepository;
use App\Repositories\Alert\AlertUser\AlertUserRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlertRepository extends AbstractRepository
{
    protected UserService $user;
    protected LogService $logService;
    protected AlertUserRepository $alertUserRepository;

    public function __construct(
        Alert $model,
        UserService $user,
        LogService $logService,
        AlertUserRepository $alertUserRepository
    ) {
        parent::__construct($model);
        $this->user = $user;
        $this->logService = $logService;
        $this->alertUserRepository = $alertUserRepository;
    }

    /**
     * 🔹 Normaliza datas
     */
    private function parseDates(array $data): array
    {
        return [
            'startDate' => !empty($data['startDate'])
                ? Carbon::parse($data['startDate'])->startOfDay()
                : null,

            'endDate' => !empty($data['endDate'])
                ? Carbon::parse($data['endDate'])->endOfDay()
                : null,
        ];
    }

    /**
     * 🔹 Aplica filtro de datas
     */
    private function applyDateFilter($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * 🔹 Total geral
     */
    public function getAllUsersAlertSummary(array $data = [])
{
    ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

    // 🔹 Busca IDs de usuários com alertas (já filtrado por data)
    $userIds = $this->alertUserRepository
        ->getUsersWithAlerts($startDate, $endDate);

    return collect($userIds)->map(function ($userId) use ($startDate, $endDate) {

        $user = \App\Models\User::find($userId);

        if (!$user) {
            return null;
        }
        // 🔹 Contagem por status
        $summary = $this->alertUserRepository
            ->countAlertsByUserGrouped($userId, $startDate, $endDate);

        return [
            'id'              => $user->id,
            'name'            => $user->first_name . ' ' . $user->last_name,
            'email'           => $user->email,
            'inactive_alerts' => $summary['closed'] ?? 0,
            'new'             => $summary['new'] ?? 0,
            'validation'      => $summary['validation'] ?? 0,
            'supervision'     => $summary['supervision'] ?? 0,
        ];
    })->filter()->values();
}
    public function getTotalAlerts(array $data): array
    {
        ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

        $total = $this->applyDateFilter(
            $this->model->newQuery(),
            $startDate,
            $endDate
        )->count();

        return [
            'total' => $total,

            'by_status' => $this->countByField('is_active', [
                1 => 'new',
                2 => 'validation',
                3 => 'supervision',
                0 => 'closed',
            ], $data),

            'by_level' => $this->countByField('level', [
                'Alto' => 'Alto',
                'Médio' => 'Médio',
                'Baixo' => 'Baixo',
            ], $data),

            'by_category' => $this->countByCategory($data),

            'by_type' => $this->countByField('type', [
                'QuickPolicyReplacementDetected' => 'Substituição rápida de apólice',
                'EarlyRedemptionDetected' => 'Resgate antecipado de apólice',
                'HighPremiumLowRisk' => 'Prémio elevado com risco baixo',
                'RepeatedReplacementOrCancellation' => 'Substituição ou cancelamento repetido',
                'PolicyChurn' => 'Churn de apólices',
                'HighCapitalIncrease' => 'Aumento elevado de capital',

                'ThirdPartyPayments' => 'Pagamentos por terceiros',
                'FrequentBeneficiaryChanges' => 'Alterações de beneficiários',
                'HighRiskGeography' => 'Geografia de risco',
                'OverpaymentRefund' => 'Sobrepagamento',
            ], $data),

            'by_month' => $this->getTotalAlertsByMonth($data),
            'users' => $this->getAllUsersAlertSummary($data),

            'particular_entity' => $this->entityStats(2, $data),
            'coletive_entity' => $this->entityStats(1, $data),
        ];
    }

    /**
     * 🔹 Contagem genérica
     */
    private function countByField(string $field, array $map, array $data): array
    {
        ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

        $query = $this->applyDateFilter(
            $this->model->newQuery(),
            $startDate,
            $endDate
        );

        $counts = $query
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field);

        return collect($map)->mapWithKeys(
            fn($label, $value) => [$label => $counts[$value] ?? 0]
        )->toArray();
    }

    /**
     * 🔹 Contagem por categoria
     */
    private function countByCategory(array $data): array
    {
        ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

        return $this->applyDateFilter(
            $this->model->newQuery(),
            $startDate,
            $endDate
        )
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->only(['KYC', 'KYT'])
            ->toArray();
    }

    /**
     * 🔹 Estatísticas por entidade
     */
    private function entityStats(int $type, array $data): array
    {
        ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

        $query = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', $type);

        $query = $this->applyDateFilter($query, $startDate, $endDate);

        return [
            'total' => (clone $query)->count(),

            'by_level' => (clone $query)
                ->select('alert.level', DB::raw('COUNT(*) as total'))
                ->groupBy('alert.level')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * 🔹 Alertas por mês
     */
    public function getTotalAlertsByMonth(array $data): array
    {
        ['startDate' => $startDate, 'endDate' => $endDate] = $this->parseDates($data);

        $months = collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse();

        $query = $this->applyDateFilter(
            $this->model->newQuery(),
            $startDate,
            $endDate
        );

        $alerts = $query
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return $months->map(fn($month) => [
            'month' => $month->format('M'),
            'total' => $alerts[$month->format('Y-m')] ?? 0,
        ])->toArray();
    }

    /**
     * 🔹 Update status
     */
    public function updateStatus(array $data, int $id): Alert
    {
        $data['assigned_to'] = Auth::id();

        if ($data['is_active'] == 0) {
            $data['alert_priority'] = 0;
        }

        $alert = $this->model->findOrFail($id);
        $alert->update($data);

        $this->alertUserRepository->updateAlertUser([
            "is_read" => $data['is_active']
        ], $id);

        return $alert;
    }
}