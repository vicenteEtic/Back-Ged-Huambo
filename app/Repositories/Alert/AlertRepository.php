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

    /**
     * Totais de alertas por mês (últimos 12 meses)
     */


   public function getTotalAlertsByMonth(array $data = []): array
{
    // 🔹 Normalizar datas (SEM conversão de timezone desnecessária)
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Últimos 12 meses (forçando UTC para consistência)
    $months = collect(range(0, 11))
        ->map(fn($i) => Carbon::now('UTC')->subMonths($i)->startOfMonth())
        ->reverse()
        ->values();

    // 🔹 Nova query limpa
    $query = $this->model->newQuery();

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    // 🔹 Agrupar por mês (forma segura)
    $alertsByMonth = $query
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
        ->selectRaw("COUNT(*) as total")
        ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
        ->pluck('total', 'month');

    // 🔹 Mapear todos os meses (mesmo os que não têm dados)
    return $months->map(function (Carbon $month) use ($alertsByMonth) {
        $key = $month->format('Y-m');

        return [
            'month' => $month->format('F'), // ou translatedFormat('F')
            'total' => $alertsByMonth[$key] ?? 0,
        ];
    })->toArray();
}

    /**
     * Totais gerais de alertas
     */
    public function getAllUsersAlertSummary(array $data = [])
{
    // 🔹 Datas SEM conversão de timezone
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 IDs dos usuários com alertas
    $userIds = $this->alertUserRepository
        ->getUsersWithAlerts($startDate, $endDate);

    // 🔹 Buscar todos os usuários de uma vez (evita N+1)
    $users = \App\Models\User::whereIn('id', $userIds)
        ->get()
        ->keyBy('id');

    return collect($userIds)->map(function ($userId) use ($users, $startDate, $endDate) {

        $user = $users[$userId] ?? null;

        // 🔹 Segurança
        if (!$user) {
            return null;
        }

        // 🔹 Resumo por status
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
    })
    ->filter() // remove nulls
    ->values();
}



   public function particularEntity(array $data = []): array
{
    // 🔹 Datas SEM conversão de timezone
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    $baseQuery = DB::table('alert')
        ->join('entities', 'alert.entity_id', '=', 'entities.id')
        ->where('entities.entity_type', 2);

    // 🔹 Filtro de datas
    if ($startDate && $endDate) {
        $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $baseQuery->where('alert.created_at', '>=', $startDate);
    } elseif ($endDate) {
        $baseQuery->where('alert.created_at', '<=', $endDate);
    }

    // 🔹 Agrupado por nível
    $alertasPorNivel = (clone $baseQuery)
        ->select('alert.level', DB::raw('COUNT(*) as total'))
        ->groupBy('alert.level')
        ->get();

    // 🔹 Total
    $totalAlertas = (clone $baseQuery)->count();

    return [
        'total' => $totalAlertas,
        'byLevel' => $alertasPorNivel->toArray(),
    ];
}


  public function particularEntityTransation(array $data = []): array
{
    // 🔹 Datas sem conversão de timezone desnecessária
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Query base
    $baseQuery = DB::table('alert')
        ->join('entities', 'alert.entity_id', '=', 'entities.id')
        ->where('entities.entity_type', 2)
        ->where('category', 'KYT');

    // 🔹 Aplicar filtro de datas de forma reutilizável
    if ($startDate && $endDate) {
        $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $baseQuery->where('alert.created_at', '>=', $startDate);
    } elseif ($endDate) {
        $baseQuery->where('alert.created_at', '<=', $endDate);
    }

    // 🔹 Agrupar por nível
    $alertasPorNivel = (clone $baseQuery)
        ->select('alert.level', DB::raw('COUNT(*) as total'))
        ->groupBy('alert.level')
        ->get();

    // 🔹 Total geral
    $totalAlertas = (clone $baseQuery)->count();

    return [
        'total' => $totalAlertas,
        'byLevel' => $alertasPorNivel->toArray(),
    ];
}

   public function coletiveEntityTransation(array $data = []): array
{
    // 🔹 Datas sem conversão de timezone
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Query base
    $baseQuery = DB::table('alert')
        ->join('entities', 'alert.entity_id', '=', 'entities.id')
        ->where('entities.entity_type', 1)
        ->where('category', 'KYT');

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $baseQuery->where('alert.created_at', '>=', $startDate);
    } elseif ($endDate) {
        $baseQuery->where('alert.created_at', '<=', $endDate);
    }

    // 🔹 Agrupado por nível
    $alertasPorNivel = (clone $baseQuery)
        ->select('alert.level', DB::raw('COUNT(*) as total'))
        ->groupBy('alert.level')
        ->get();

    // 🔹 Total geral
    $totalAlertas = (clone $baseQuery)->count();

    return [
        'total' => $totalAlertas,
        'byLevel' => $alertasPorNivel->toArray(),
    ];
}


   public function coletiveEntity(array $data = []): array
{
    // 🔹 Datas sem conversão de timezone
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Query base
    $baseQuery = DB::table('alert')
        ->join('entities', 'alert.entity_id', '=', 'entities.id')
        ->where('entities.entity_type', 1);

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $baseQuery->whereBetween('alert.created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $baseQuery->where('alert.created_at', '>=', $startDate);
    } elseif ($endDate) {
        $baseQuery->where('alert.created_at', '<=', $endDate);
    }

    // 🔹 Agrupado por nível
    $alertasPorNivel = (clone $baseQuery)
        ->select('alert.level', DB::raw('COUNT(*) as total'))
        ->groupBy('alert.level')
        ->get();

    // 🔹 Total geral
    $totalAlertas = (clone $baseQuery)->count();

    return [
        'total' => $totalAlertas,
        'byLevel' => $alertasPorNivel->toArray(),
    ];
}



    public function getTotalAlerts($data): array
    {
        return [
       

            'total' => $this->model
            ->when(!empty($data['startDate']), function ($query) use ($data) {
                $startDate = Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay();
                $query->where('created_at', '>=', $startDate);
            })
            ->when(!empty($data['endDate']), function ($query) use ($data) {
                $endDate = Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay();
                $query->where('created_at', '<=', $endDate);
            })
            ->count(),
            'transation' => [

                "particularEntity" => $this->particularEntityTransation($data),
                "coletiveEntit" => $this->coletiveEntityTransation($data),
                'by_type' => $this->countByField('type', [
                    "Substituição rápida de apólice" => 'QuickPolicyReplacementDetected',
                    "Resgate antecipado de apólice" => 'EarlyRedemptionDetected',
                    "Prémio elevado com risco baixo" => 'HighPremiumLowRisk',
                    "Substituição ou cancelamento repetido" => 'RepeatedReplacementOrCancellation',
                    "Churn de apólices (trocas frequentes)" => 'PolicyChurn',
                    "Aumento elevado de capital na apólice" => 'HighCapitalIncrease',

                    // NOVOS CENÁRIOS KYT

                    "Pagamentos por terceiros sem relação clara" => 'ThirdPartyPayments',
                    "Alterações frequentes de beneficiários" => 'FrequentBeneficiaryChanges',
                    "Ligação a jurisdições de alto risco" => 'HighRiskGeography',
                    "Sobrepagamento seguido de reembolso a terceiros" => 'OverpaymentRefund',



                ], $data),


            ],
            'ParticularEntity' => $this->ParticularEntity($data),
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

            'by_communication' => $this->countByField('is_reported', [
                1 => 'with_communication',
                0 => 'without_communication',
            ], $data),

            'pep' => $this->model
                ->when(!empty($data['startDate']), function ($query) use ($data) {
                    $startDate = Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay();
                    $query->where('created_at', '>=', $startDate);
                })
                ->when(!empty($data['endDate']), function ($query) use ($data) {
                    $endDate = Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay();
                    $query->where('created_at', '<=', $endDate);
                })
                ->where('type', 'PEP')
                ->count(),

            'sanction' => $this->model
                ->when(!empty($data['startDate']), function ($query) use ($data) {
                    $startDate = Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay();
                    $query->where('created_at', '>=', $startDate);
                })
                ->when(!empty($data['endDate']), function ($query) use ($data) {
                    $endDate = Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay();
                    $query->where('created_at', '<=', $endDate);
                })
                ->where('type', 'SANCTIONS')
                ->count(),

            'AML' => $this->model
                ->when(!empty($data['startDate']), function ($query) use ($data) {
                    $startDate = Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay();
                    $query->where('created_at', '>=', $startDate);
                })
                ->when(!empty($data['endDate']), function ($query) use ($data) {
                    $endDate = Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay();
                    $query->where('created_at', '<=', $endDate);
                })
                ->where('type', 'AML')
                ->count(),

            'by_type' => $this->countByCategory($data),
            'by_level' => $this->countByLevel('level', [
                "Alto" => 'Alto',
                "Médio" => 'Médio',
                "Baixo" => 'Baixo',

            ], $data),


            'users' => $this->getAllUsersAlertSummary($data),
            'by_month' => $this->getTotalAlertsByMonth($data),
        ];
    }
    /**
     * Contagem genérica por campo
     */
  private function countByField(string $field, array $map, array $data = []): array
{
    // 🔹 Preparar datas
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Nova query
    $query = $this->model->newQuery();

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    // 🔹 Contagem agrupada pelo campo
    $counts = $query
        ->select($field, DB::raw('COUNT(*) as total'))
        ->groupBy($field)
        ->pluck('total', $field)
        ->toArray();

    // 🔹 Mapear os resultados usando $map
    return collect($map)->mapWithKeys(
        fn($label, $value) => [$label => $counts[$value] ?? 0]
    )->toArray();
}



private function countByLevel(string $field, array $map, array $data = []): array
{
    // 🔹 Preparar datas
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Nova query
    $query = $this->model->newQuery();

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    // 🔹 Contagem agrupada pelo campo
    $counts = $query
        ->select($field, DB::raw('COUNT(*) as total'))
        ->groupBy($field)
        ->pluck('total', $field)
        ->toArray(); // garante array seguro

    // 🔹 Mapear os resultados usando $map
    return collect($map)->mapWithKeys(
        fn($label, $value) => [$label => $counts[$value] ?? 0]
    )->toArray();
}

    /**
     * Contagem por categoria (KYC / KYT)
     */
   private function countByCategory(array $data = []): array
{
    // 🔹 Preparar datas
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // 🔹 Nova query independente
    $query = $this->model->newQuery();

    // 🔹 Aplicar filtro de datas
    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    // 🔹 Contagem por categoria
    $counts = $query
        ->select('category', DB::raw('COUNT(*) as total'))
        ->groupBy('category')
        ->pluck('total', 'category')
        ->only(['KYC', 'KYT'])
        ->toArray();

    return $counts;
}

    /**
     * Atualizar status do alerta
     */
    public function updateStatus(array $data, int $id): Alert
    {
        $data['assigned_to'] =
            Auth::user()->id;
        if ($data['is_active'] == 0) {
            $data['alert_priority'] = 0;
        }
        $alert = $this->model->findOrFail($id);
        $alert->update($data);
        $datalert = [
            "is_read" => $data['is_active']
        ];

        $this->alertUserRepository->updateAlertUser($datalert, $id);
        return $alert;
    }
}
