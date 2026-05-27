<?php

namespace App\Repositories\Alert;

use App\Models\Alert\Alert;
use App\Repositories\AbstractRepository;
use App\Repositories\Alert\AlertUser\AlertUserRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Carbon\Carbon;
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
     * ============================================
     * DATE NORMALIZER
     * ============================================
     */
    private function normalizeDate(?string $date, bool $end = false): ?Carbon
    {
        if (empty($date)) return null;

        try {
            $date = Carbon::parse($date);
            return $end ? $date->endOfDay() : $date->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ============================================
     * DATE FILTER (GLOBAL FIX)
     * ============================================
     */
    private function applyDateFilter($query, array $data = [], string $column = 'created_at')
    {
        $startDate = $this->normalizeDate($data['startDate'] ?? null, false);
        $endDate   = $this->normalizeDate($data['endDate'] ?? null, true);

        if ($startDate && $endDate) {
            $query->whereBetween($column, [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where($column, '>=', $startDate);
        } elseif ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }

    private function baseQuery(array $data = [])
    {
        return $this->applyDateFilter(
            $this->model->newQuery(),
            $data,
            'created_at'
        );
    }

    /**
     * ============================================
     * MONTHLY
     * ============================================
     */
    public function getTotalAlertsByMonth(array $data = []): array
    {
        $query = $this->baseQuery($data);

        $alertsByMonth = $query
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->pluck('total', 'month');

        return collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->map(fn(Carbon $month) => [
                'month' => $month->format('F'),
                'total' => $alertsByMonth[$month->format('Y-m')] ?? 0,
            ])
            ->values()
            ->toArray();
    }

    /**
     * ============================================
     * USERS SUMMARY
     * ============================================
     */
    public function getAllUsersAlertSummary(array $data = [])
    {
        $startDate = $this->normalizeDate($data['startDate'] ?? null, false);
        $endDate   = $this->normalizeDate($data['endDate'] ?? null, true);

        $userIds = $this->alertUserRepository->getUsersWithAlerts($startDate, $endDate);

        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        return collect($userIds)
            ->map(function ($userId) use ($users, $startDate, $endDate) {

                $user = $users[$userId] ?? null;
                if (!$user) return null;

                $summary = $this->alertUserRepository->countAlertsByUserGrouped(
                    $userId,
                    $startDate,
                    $endDate
                );

                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,

                    'inactive_alerts' => $summary['closed'] ?? 0,
                    'new' => $summary['new'] ?? 0,
                    'validation' => $summary['validation'] ?? 0,
                    'supervision' => $summary['supervision'] ?? 0,

                    'total' =>
                        ($summary['closed'] ?? 0) +
                        ($summary['new'] ?? 0) +
                        ($summary['validation'] ?? 0) +
                        ($summary['supervision'] ?? 0),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * ============================================
     * ENTITY SUMMARY
     * ============================================
     */
    private function entitySummary(int $entityType, array $data = [], ?string $category = null)
    {
        $query = DB::table('alert as a')
            ->join('entities as e', 'a.entity_id', '=', 'e.id')
            ->where('e.entity_type', $entityType);

        if ($category) {
            $query->where('a.category', $category);
        }

        $query = $this->applyDateFilter($query, $data, 'a.created_at');

        $grouped = (clone $query)
            ->select('a.level', DB::raw('COUNT(*) as total'))
            ->groupBy('a.level')
            ->get();

        $total = (clone $query)->count();

        return [
            'total' => $total,
            'byLevel' => $grouped->toArray(),
        ];
    }

    public function particularEntity(array $data = [])
    {
        return $this->entitySummary(2, $data);
    }

    public function coletiveEntity(array $data = [])
    {
        return $this->entitySummary(1, $data);
    }

    public function particularEntityTransation(array $data = [])
    {
        return $this->entitySummary(2, $data, 'KYT');
    }

    public function coletiveEntityTransation(array $data = [])
    {
        return $this->entitySummary(1, $data, 'KYT');
    }

    /**
     * ============================================
     * COUNT BY FIELD
     * ============================================
     */
    private function countByField(string $field, array $map, array $data = [], array $filters = [])
    {
        $query = $this->baseQuery($data);

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        $counts = $query
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field)
            ->toArray();

        return collect($map)->mapWithKeys(fn($label, $value) => [
            $label => $counts[$value] ?? 0,
        ])->toArray();
    }

    private function countByCategory(array $data = [])
    {
        $query = $this->baseQuery($data);

        return $query
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->only(['KYC', 'KYT'])
            ->toArray();
    }

    /**
     * ============================================
     * MAIN DASHBOARD
     * ============================================
     */
    public function getTotalAlerts(array $data): array
    {
        $baseQuery = $this->baseQuery($data);

        return [
            'total' => (clone $baseQuery)->count(),

            'transation' => [
                'particularEntity' => $this->particularEntityTransation($data),
                'coletiveEntity' => $this->coletiveEntityTransation($data),

                'by_type' => $this->countByField('type', [
                    "HighCapitalIncrease" => "Aumento abrupto",
                    "EarlyRedemptionDetected" => "Resgate precoce",
                    "HighPremiumLowRisk" => "Prémio incompatível",
                    "PolicyChurn" => "Churn",
                    "RepeatedReplacementOrCancellation" => "Cancelamentos",
                    "QuickPolicyReplacementDetected" => "Substituição rápida",
                    "ThirdPartyPayments" => "Pagamentos terceiros",
                    "FrequentBeneficiaryChanges" => "Beneficiários",
                    "HighRiskGeography" => "Risco geográfico",
                    "OverpaymentRefund" => "Reembolso",
                ], $data),
            ],

            'ParticularEntity' => $this->particularEntity($data),
            'coletiveEntity' => $this->coletiveEntity($data),

            'by_status' => $this->countByField('is_active', [
                1 => 'new',
                2 => 'validation',
                3 => 'supervision',
                0 => 'closed',
            ], $data),

            'by_sanctioned' => $this->countByField('is_sanctioned', [
                1 => 'with_communication',
                0 => 'without_communication',
            ], $data),

            'by_communication' => $this->countByField(
                'is_reported',
                [
                    1 => 'with_communication',
                    0 => 'without_communication',
                ],
                $data,
                ['is_active' => 0]
            ),

            'pep' => (clone $baseQuery)->where('type', 'PEP')->count(),
            'sanction' => (clone $baseQuery)->where('type', 'SANCTIONS')->count(),
            'AML' => (clone $baseQuery)->where('type', 'AML')->count(),

            'by_category' => $this->countByCategory($data),
            'by_level' => $this->countByField('level', [
                "Alto" => "Alto",
                "Médio" => "Médio",
                "Baixo" => "Baixo",
            ], $data),

            'users' => $this->getAllUsersAlertSummary($data),
            'by_month' => $this->getTotalAlertsByMonth($data),
        ];
    }
}