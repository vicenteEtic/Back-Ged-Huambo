<?php

namespace App\Repositories\Alert;

use App\Models\Alert\Alert;
use App\Repositories\AbstractRepository;
use App\Services\Log\LogService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AlertRepository extends AbstractRepository
{
    protected UserService $user;
    protected LogService $logService;

    public function __construct(
        Alert $model,
        UserService $user,
        LogService $logService
    ) {
        parent::__construct($model);
        $this->user = $user;
        $this->logService = $logService;
    }

    /**
     * Totais de alertas por mês (últimos 12 meses)
     */
    public function getTotalAlertsByMonth(): array
    {
        $months = collect(range(0, 11))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->startOfMonth())
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
    public function getTotalAlerts(): array
    {
        return [
            'total' => $this->model->count(),

            'by_status' => $this->countByField('is_active', [
                1 => 'new',
                2 => 'validation',
                3 => 'supervision',
                0 => 'closed',
            ]),

            'by_communication' => $this->countByField('is_sanctioned', [
                1 => 'with_communication',
                0 => 'without_communication',
            ]),

            'by_reported' => $this->countByField('is_reported', [
                1 => 'reported',
                0 => 'not_reported',
            ]),

            'pep' => $this->model->where('is_pep', 1)->count(),
            'sanction' => $this->model->where('is_sanctioned', 1)->count(),

            'by_type' => $this->countByCategory(),
             'by_level' =>$this->countByLevel('level', [
                "Alto" => 'Alto',
                 "Médio" => 'Médio',
                 "Baixo" => 'Baixo',
               
            ]),
             
             
         

            

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
            fn ($label, $value) => [$label => $counts[$value] ?? 0]
        )->toArray();
    }


   
    private function countByLevel(string $field, array $map): array
    {
        $counts = $this->model
            ->select($field, DB::raw('COUNT(*) as total'))
            ->groupBy($field)
            ->pluck('total', $field);

        return collect($map)->mapWithKeys(
            fn ($label, $value) => [$label => $counts[$value] ?? 0]
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
        $alert = $this->model->findOrFail($id);
        $alert->update($data);

        return $alert;
    }
}