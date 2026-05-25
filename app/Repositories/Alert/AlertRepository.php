<?php

namespace App\Repositories\Alert;

use App\Models\Alert\Alert;
use App\Models\Entities\Entities;
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

    public function getTotalAlertsByMonth(array $data = []): array
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $months = collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->values();

        $query = $this->model->newQuery();

        if ($startDate && $endDate) $query->whereBetween('created_at', [$startDate, $endDate]);
        elseif ($startDate) $query->where('created_at', '>=', $startDate);
        elseif ($endDate) $query->where('created_at', '<=', $endDate);

        $alertsByMonth = $query
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->pluck('total', 'month');

        return $months->map(fn(Carbon $month) => [
            'month' => $month->format('F'),
            'total' => $alertsByMonth[$month->format('Y-m')] ?? 0
        ])->toArray();
    }

    public function getAllUsersAlertSummary(array $data = [])
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $userIds = $this->alertUserRepository->getUsersWithAlerts($startDate, $endDate);

        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        return collect($userIds)->map(function ($userId) use ($users, $startDate, $endDate) {
            $user = $users[$userId] ?? null;
            if (!$user) return null;

            $summary = $this->alertUserRepository->countAlertsByUserGrouped($userId, $startDate, $endDate);

            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'inactive_alerts' => $summary['closed'] ?? 0,
                'new' => $summary['new'] ?? 0,
                'validation' => $summary['validation'] ?? 0,
                'supervision' => $summary['supervision'] ?? 0,
                'total' => $summary['validation'] + $summary['new'] + $summary['supervision'] + $summary['closed'] ?? 0,
            ];
        })->filter()->values();
    }

    public function particularEntity(array $data = []): array
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $baseQuery = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 2);

        if ($startDate && $endDate) $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
        elseif ($startDate) $baseQuery->where('alert.created_at', '>=', $startDate);
        elseif ($endDate) $baseQuery->where('alert.created_at', '<=', $endDate);

        $alertasPorNivel = (clone $baseQuery)
            ->select('alert.level', DB::raw('COUNT(*) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = (clone $baseQuery)->count();

        return ['total' => $totalAlertas, 'byLevel' => $alertasPorNivel->toArray()];
    }

    public function particularEntityTransation(array $data = []): array
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $baseQuery = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 2)
            ->where('category', 'KYT');

        if ($startDate && $endDate) $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
        elseif ($startDate) $baseQuery->where('alert.created_at', '>=', $startDate);
        elseif ($endDate) $baseQuery->where('alert.created_at', '<=', $endDate);

        $alertasPorNivel = (clone $baseQuery)
            ->select('alert.level', DB::raw('COUNT(*) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = (clone $baseQuery)->count();

        return ['total' => $totalAlertas, 'byLevel' => $alertasPorNivel->toArray()];
    }


    public function coletiveEntityTransation(array $data = []): array
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $baseQuery = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 1)
            ->where('category', 'KYT');

        if ($startDate && $endDate) $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
        elseif ($startDate) $baseQuery->where('alert.created_at', '>=', $startDate);
        elseif ($endDate) $baseQuery->where('alert.created_at', '<=', $endDate);

        $alertasPorNivel = (clone $baseQuery)
            ->select('alert.level', DB::raw('COUNT(*) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = (clone $baseQuery)->count();

        return ['total' => $totalAlertas, 'byLevel' => $alertasPorNivel->toArray()];
    }



    public function coletiveEntity(array $data = []): array
    {
        $startDate = !empty($data['started']) ? Carbon::parse($data['started'])->startOfDay() : null;
        $endDate = !empty($data['ended']) ? Carbon::parse($data['ended'])->endOfDay() : null;

        $baseQuery = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 1);

        if ($startDate && $endDate) $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
        elseif ($startDate) $baseQuery->where('alert.created_at', '>=', $startDate);
        elseif ($endDate) $baseQuery->where('alert.created_at', '<=', $endDate);

        $alertasPorNivel = (clone $baseQuery)
            ->select('alert.level', DB::raw('COUNT(*) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = (clone $baseQuery)->count();

        return ['total' => $totalAlertas, 'byLevel' => $alertasPorNivel->toArray()];
    }

    private function countByField(
        string $field,
        array $map,
        array $data = [],
        array $filters = [],
        bool $applyFilters = true
    ): array {
        $query = $this->applyDateFilter($this->model->newQuery(), $data);

        if ($applyFilters) {
            foreach ($filters as $column => $value) {
                if ($value !== null) {
                    $query->where($column, $value);
                }
            }
        }

        $counts = $query->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field)
            ->toArray();

        return collect($map)->mapWithKeys(
            fn($label, $value) => [
                $label => $counts[$value] ?? 0
            ]
        )->toArray();
    }

    private function countByLevel(string $field, array $map, array $data = []): array
    {
        return $this->countByField($field, $map, $data);
    }

    private function countByCategory(array $data = []): array
    {
        $query = $this->applyDateFilter($this->model->newQuery(), $data);

        return $query->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->only(['KYC', 'KYT'])
            ->toArray();
    }

    public function updateStatus(array $data, int $id): Alert
    {
        $data['assigned_to'] = Auth::user()->id;
        if ($data['is_active'] == 0) $data['alert_priority'] = 0;

        $alert = $this->model->findOrFail($id);
        $alert->update($data);

        $this->alertUserRepository->updateAlertUser(["is_read" => $data['is_active']], $id);

        return $alert;
    }

    private function applyDateFilter($query, array $data)
    {
        $startDate = !empty($data['started'])
            ? Carbon::parse($data['started'])->startOfDay()
            : null;

        $endDate = !empty($data['ended'])
            ? Carbon::parse($data['ended'])->endOfDay()
            : null;

        return $query
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when($startDate && !$endDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when(!$startDate && $endDate, fn($q) => $q->where('created_at', '<=', $endDate));
    }
    public function getTotalAlerts(array $data): array
    {
        $total = $this->applyDateFilter(
            $this->model->newQuery(),
            $data
        )->count();

        $transation = [
            "particularEntity" => $this->particularEntityTransation($data),
            "coletiveEntity" => $this->coletiveEntityTransation($data),

            'by_type' => $this->countByField(
                'type',
                [
                    "Aumento abrupto e injustificado do capital seguro entre apólices" => 'HighCapitalIncrease',
                    "Resgate ou cancelamento da apólice antes de 12 meses" => 'EarlyRedemptionDetected',
                    "Prémio elevado incompatível com o risco segurado" => 'HighPremiumLowRisk',
                    "Subscrição de múltiplas apólices de curta duração" => 'PolicyChurn',
                    "Cancelamentos frequentes de Apólices num curto Período" => 'RepeatedReplacementOrCancellation',
                    "Substituição rápida de apólices" => 'QuickPolicyReplacementDetected',
                    "Pagamentos de prémios por terceiros sem relação clara com o segurado" => 'ThirdPartyPayments',
                    "Mudanças frequentes de beneficiários sem justificação aparente" => 'FrequentBeneficiaryChanges',
                    "Apólices com beneficiários ou pagamentos de jurisdições de alto risco" => 'HighRiskGeography',
                    "Sobrepagamento de prémios seguido de pedido de reembolso para terceiros" => 'OverpaymentRefund',
                ],
                $data,
                [],
                false
            ),
        ];

        $pep = $this->applyDateFilter($this->model->newQuery(), $data)
            ->where('type', 'PEP')
            ->count();

        $sanction = $this->applyDateFilter($this->model->newQuery(), $data)
            ->where('type', 'SANCTIONS')
            ->count();

        $aml = $this->applyDateFilter($this->model->newQuery(), $data)
            ->where('type', 'AML')
            ->count();

        return [
            'total' => $total,
            'transation' => $transation,
            'ParticularEntity' => $this->particularEntity($data),
            'coletiveEntity' => $this->coletiveEntity($data),

            'by_status' => $this->countByField(
                'is_active',
                [
                    1 => 'new',
                    2 => 'validation',
                    3 => 'supervision',
                    0 => 'closed',
                ],
                $data,
                [],
                false
            ),

            'by_sanctioned' => $this->countByField(
                'is_sanctioned',
                [
                    1 => 'with_communication',
                    0 => 'without_communication',
                ],
                $data,
                [],
                false
            ),

            'by_communication' => $this->countByField(
                'is_reported',
                [
                    1 => 'with_communication',
                    0 => 'without_communication',
                ],
                $data,
                ['is_active' => 0],
                true
            ),

            'pep' => $pep,
            'sanction' => $sanction,
            'AML' => $aml,

            'by_category' => $this->countByCategory($data),
            'by_level' => $this->countByLevel('level', [
                "Alto" => 'Alto',
                "Médio" => 'Médio',
                "Baixo" => 'Baixo',
            ], $data),

            'users' => $this->getAllUsersAlertSummary($data),
            'by_month' => $this->getTotalAlertsByMonth($data),
        ];
    }
}
