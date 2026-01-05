<?php

namespace App\Repositories\Alert;

use App\Models\Alert\Alert;
use App\Repositories\AbstractRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
class AlertRepository extends AbstractRepository
{
    public function __construct(Alert $model)
    {
        parent::__construct($model);
    }


    public function getTotalAlertsByMonth(): array
    {
        // Últimos 12 meses
        $months = collect(range(0, 11))->map(function ($i) {
            return Carbon::now()->subMonths($i);
        })->reverse()->values();

        // Consulta com agrupamento
        $alertsDetailed = $this->model
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as is_active")
            ->selectRaw("SUM(CASE WHEN is_active = 2 THEN 1 ELSE 0 END) as is_inative")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Monta resposta final
        $result = [];
        foreach ($months as $carbonMonth) {
            $key = $carbonMonth->format('Y-m');
            $data = $alertsDetailed[$key] ?? null;

            $result[] = [
                'month'      => $carbonMonth->translatedFormat('F'),
                'total'      => $data->total ?? 0,
                'is_pending'  => $data->is_active ?? 0,
                'is_inative' => $data->is_inative ?? 0,
                "by_type"=>[

                ]
            ];
        }

        return $result;
    }


    public function countwithTYpe($byType){
            $counts = $this->model
            ::where('')-> count()
           ;
    }
    public function getTotalAlerts(): array
    {
        $counts = $this->model
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as is_active')
            ->selectRaw('SUM(CASE WHEN is_active = 2 THEN 1 ELSE 0 END) as is_inative')
            ->first();

        // Contagem por "level"
        $byLevel = $this->model
            ->select('level', \DB::raw('COUNT(*) as total'))
            ->groupBy('level')
            ->pluck('total', 'level'); // retorna formato [level => total]

        // Contagem por "type"
        $byType = $this->model
            ->select('type', \DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type'); // retorna formato [type => total]

        return [
            'total'      => $counts->total ?? 0,
            'is_active'  => $counts->is_active ?? 0,
            'is_inative' => $counts->is_inative ?? 0,
            'by_level'   => $byLevel ?? 0,
            'by_type'    => $byType ?? 0,
            'by_month'   => $this->getTotalAlertsByMonth(),
        ];
    }



}
