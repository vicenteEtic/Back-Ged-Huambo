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

    public function updateStatus(array $data, int $id)
    {
        $alert = $this->model->findOrFail($id);
        $alert->update($data);
        return $alert;
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        if (!$orderByParams) {
            $orderByParams = [
                ['column' => 'alert_priority', 'dir' => 'desc'],
                ['column' => 'created_at', 'dir' => 'desc'],
            ];
        }

        // Converte [['column' => 'x', 'dir' => 'y']] para ['x' => 'y']
        // Formato que o FilterHandler::applyOrder() espera
        if (isset($orderByParams[0]['column'])) {
            $normalized = [];
            foreach ($orderByParams as $param) {
                $normalized[$param['column']] = $param['dir'] ?? 'asc';
            }
            $orderByParams = $normalized;
        }

        return parent::index($paginate, $filterParams, $orderByParams, $relationships);
    }

    // ============================================
    // DATE HANDLER (UNIFICADO E SEGURO)
    // ============================================
    private function parseDate(?string $date, bool $end = false): ?Carbon
    {
        if (!$date) return null;

        $d = Carbon::parse($date);

        return $end ? $d->endOfDay() : $d->startOfDay();
    }

    private function baseQuery(array $data = [])
    {
        $query = $this->model->newQuery();

        $start = $this->parseDate($data['startDate'] ?? null, false);
        $end   = $this->parseDate($data['endDate'] ?? null, true);

        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('created_at', '>=', $start);
        } elseif ($end) {
            $query->where('created_at', '<=', $end);
        }

        return $query;
    }

    // ============================================
    // MONTHS
    // ============================================
    public function getTotalAlertsByMonth(array $data = []): array
    {
        $query = $this->baseQuery($data);

        $result = $query
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->pluck('total', 'month');

        return collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->map(fn($m) => [
                'month' => $m->format('F'),
                'total' => $result[$m->format('Y-m')] ?? 0,
            ])
            ->values()
            ->toArray();
    }

    // ============================================
    // USERS SUMMARY
    // ============================================
    public function getAllUsersAlertSummary(array $data = [])
    {
        $start = $this->parseDate($data['startDate'] ?? null, false);
        $end   = $this->parseDate($data['endDate'] ?? null, true);

        $userIds = $this->alertUserRepository->getUsersWithAlerts($start, $end);

        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        return collect($userIds)->map(function ($id) use ($users, $start, $end) {

            $user = $users[$id] ?? null;
            if (!$user) return null;

            $summary = $this->alertUserRepository->countAlertsByUserGrouped($id, $start, $end);

            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,

                'inactive_alerts' => $summary['closed'] ?? 0,
                'new' => $summary['new'] ?? 0,
                'validation' => $summary['validation'] ?? 0,
                'supervision' => $summary['supervision'] ?? 0,
                    'total' => $summary['closed']+($summary['new'] ?? 0)+($summary['validation'] ?? 0)+($summary['supervision'] ?? 0),
            ];
        })->filter()->values();
    }

    // ============================================
    // ENTITY
    // ============================================
    private function entitySummary(int $type, array $data = [], ?string $category = null)
    {
        $query = DB::table('alert as a')
            ->join('entities as e', 'a.entity_id', '=', 'e.id')
            ->where('e.entity_type', $type);

        if ($category) {
            $query->where('a.category', $category);
        }

        $start = $this->parseDate($data['startDate'] ?? null, false);
        $end   = $this->parseDate($data['endDate'] ?? null, true);

        if ($start && $end) {
            $query->whereBetween('a.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('a.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('a.created_at', '<=', $end);
        }

        $group = (clone $query)
            ->select('a.level', DB::raw('COUNT(*) as total'))
            ->groupBy('a.level')
            ->get();

        return [
            'total' => (clone $query)->count(),
            'byLevel' => $group->toArray(),
        ];
    }

    public function particularEntity(array $data = []) { return $this->entitySummary(2, $data); }
    public function coletiveEntity(array $data = []) { return $this->entitySummary(1, $data); }
    public function particularEntityTransation(array $data = []) { return $this->entitySummary(2, $data, 'KYT'); }
    public function coletiveEntityTransation(array $data = []) { return $this->entitySummary(1, $data, 'KYT'); }

    // ============================================
    // COUNT FIELD
    // ============================================
    private function countByField(string $field, array $map, array $data = [], array $filters = [])
    {
        $query = $this->baseQuery($data);

        foreach ($filters as $col => $val) {
            $query->where($col, $val);
        }

        $counts = $query
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field)
            ->toArray();

        return collect($map)->mapWithKeys(fn($label, $key) => [
            $label => $counts[$key] ?? 0,
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

    // ============================================
    // MAIN DASHBOARD
    // ============================================
    public function getTotalAlerts(array $data): array
    {
        $base = $this->baseQuery($data);

        return [
            'total' => (clone $base)->count(),

            'transation' => [
                'particularEntity' => $this->particularEntityTransation($data),
                'coletiveEntity' => $this->coletiveEntityTransation($data),

                'by_type' => $this->countByField('type', collect(config('kyt.scenario_names'))->mapWithKeys(fn ($name, $slug) => [
                    $name => collect(explode('_', $slug))->map(fn ($p) => ucfirst($p))->implode(''),
                ])->all(), $data),
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
                [1 => 'with_communication', 0 => 'without_communication'],
                $data,
                ['is_active' => 0]
            ),

            'pep' => (clone $base)->where('type', 'PEP')->count(),
            'sanction' => (clone $base)->where('type', 'SANCTIONS')->count(),
            'AML' => (clone $base)->where('type', 'AML')->count(),

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