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
    public function getTotalAlertsByMonth(): array
    {
        $months = collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->values();

        $alertsByMonth = $this->model
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return $months->map(function (Carbon $month) use ($alertsByMonth) {
            $key = $month->format('Y-m');

            return [
                'month' => $month->translatedFormat('F'),
                'total' => $alertsByMonth[$key] ?? 0,
            ];
        })->toArray();
    }

    /**
     * Totais gerais de alertas
     */
    public function getAllUsersAlertSummary()
    {

        // Busca todos os usuários que têm alertas associados
        $userIds = $this->alertUserRepository->getUsersWithAlerts();

        return collect($userIds)->map(function ($userId) {
            $user = \App\Models\User::find($userId);

            // Resumo por status do usuário
            $summary = $this->alertUserRepository->countAlertsByUserGrouped($userId);

            return [
                'id'              => $user->id,
                'name'            => $user->first_name . ' ' . $user->last_name,
                'email'           => $user->email,
                //  'active_alerts'   => $summary['total_active'] ?? 0,
                'inactive_alerts' => $summary['closed'] ?? 0,
                'new'             => $summary['new'] ?? 0,
                'validation'      => $summary['validation'] ?? 0,
                'supervision'     => $summary['supervision'] ?? 0,
            ];
        })->values();
    }



    public function particularEntity(): array
    {
        $alertasPorNivel = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 2)
            ->select('alert.level', DB::raw('COUNT(alert.id) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 2)
            ->count();

        return [
            'total' => $totalAlertas,
            'byLevel' => $alertasPorNivel->toArray(),
        ];
    }


    public function particularEntityTransation(): array
    {
        $alertasPorNivel = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 2)
            ->where('category', 'KYT')
            ->select('alert.level', DB::raw('COUNT(alert.id) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('category', 'KYT')
            ->where('entities.entity_type', 2)
            ->count();

        return [
            'total' => $totalAlertas,
            'byLevel' => $alertasPorNivel->toArray(),
        ];
    }

    public function coletiveEntitytTrsantion(): array
    {
        $alertasPorNivel = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 1)
            ->where('category', 'KYT')
            ->select('alert.level', DB::raw('COUNT(alert.id) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('category', 'KYT')
            ->where('entities.entity_type', 1)
            ->count();

        return [
            'total' => $totalAlertas,
            'byLevel' => $alertasPorNivel->toArray(),
        ];
    }


    public function coletiveEntity(): array
    {
        $alertasPorNivel = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 1)
            ->select('alert.level', DB::raw('COUNT(alert.id) as total'))
            ->groupBy('alert.level')
            ->get();

        $totalAlertas = DB::table('alert')
            ->join('entities', 'alert.entity_id', '=', 'entities.id')
            ->where('entities.entity_type', 1)
            ->count();

        return [
            'total' => $totalAlertas,
            'byLevel' => $alertasPorNivel->toArray(),
        ];
    }




    public function getTotalAlerts(): array
    {
        return [
            'total' => $this->model->count(),
            'transation' => [
                
                "particularEntity" => $this->particularEntityTransation(),
                "coletiveEntit" => $this->coletiveEntitytTrsantion(),
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
                


                ]),


            ],
            'ParticularEntity' => $this->ParticularEntity(),
            'coletiveEntity' => $this->coletiveEntity(),
            'by_status' => $this->countByField('is_active', [
                1 => 'new',
                2 => 'validation',
                3 => 'supervision',
                0 => 'closed',
            ]),

            'by_sanctioned' => $this->countByField('is_sanctioned', [
                1 => 'with_communication',
                0 => 'without_communication',
            ]),

            'by_communication' => $this->countByField('is_reported', [
                1 => 'with_communication',
                0 => 'without_communication',
            ]),

            'pep' => $this->model->where('type', 'PEP')->count(),
            'sanction' => $this->model->where('type', 'SANCTIONS')->count(),
            'AML' => $this->model->where('type', 'AML')->count(),

            'by_type' => $this->countByCategory(),
            'by_level' => $this->countByLevel('level', [
                "Alto" => 'Alto',
                "Médio" => 'Médio',
                "Baixo" => 'Baixo',

            ]),


            'users' => $this->getAllUsersAlertSummary(),
            'by_month' => $this->getTotalAlertsByMonth(),
        ];
    }
    /**
     * Contagem genérica por campo
     */
    private function countByField(string $field, array $map): array
    {
        $counts = $this->model
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field);

        return collect($map)->mapWithKeys(
            fn($label, $value) => [$label => $counts[$value] ?? 0]
        )->toArray();
    }




    private function countByLevel(string $field, array $map): array
    {
        $counts = $this->model
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field);

        return collect($map)->mapWithKeys(
            fn($label, $value) => [$label => $counts[$value] ?? 0]
        )->toArray();
    }

    /**
     * Contagem por categoria (KYC / KYT)
     */
    private function countByCategory(): array
    {
        return $this->model
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->only(['KYC', 'KYT'])
            ->toArray();
    }

    /**
     * Atualizar status do alerta
     */
    public function updateStatus(array $data, int $id): Alert
    {
        $data['assigned_to']=
        Auth::user()->id;
        
        $alert = $this->model->findOrFail($id);
        $alert->update($data);
        $datalert = [
            "is_read" => $data['is_active']
        ];

        $this->alertUserRepository->updateAlertUser($datalert, $id);
        return $alert;
    }
}
