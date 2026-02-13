<?php

namespace App\Repositories\Entities;

use App\Enum\TypeEntity;
use Illuminate\Support\Collection;
use App\Models\Entities\RiskAssessment;
use App\Repositories\AbstractRepository;
use Carbon\Carbon;

class RiskAssessmentRepository extends AbstractRepository
{
    private const RISK_LEVELS = [
        'Baixo' => 'total_baixo',
        'Médio' => 'total_medio',
        'Alto' => 'total_alto'
    ];

    private const DEFAULT_RESULT = [
        'name' => 'N/A',
        'total_baixo' => 0,
        'total_medio' => 0,
        'total_alto' => 0,
        'total_geral' => 0
    ];

    public function __construct(RiskAssessment $model)
    {
        parent::__construct($model);
    }

    // =====================
    // MÉTODO CENTRAL DE ESTATÍSTICAS
    // =====================
    private function getRiskLevelSummaryFixed(
        string $groupByRelation,
        array $data = [],
        bool $filterByEntityType = true
    ): array {
        $startDate = !empty($data['startDate']) ? Carbon::parse($data['startDate'])->startOfDay() : null;
        $endDate = !empty($data['endDate']) ? Carbon::parse($data['endDate'])->endOfDay() : null;

        $entityTypes = $filterByEntityType ? [TypeEntity::COLECTIVA, TypeEntity::SINGULAR] : [null];
        $finalResults = [];

        foreach ($entityTypes as $type) {
            $query = $this->model->with($groupByRelation)->whereNotNull('risk_level');

            if ($type !== null) {
                $query->whereHas('entity', fn($q) => $q->where('entity_type', $type));
            }

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $dataResult = $query->get()->map(function ($item) use ($groupByRelation) {
                $name = 'N/A';

                if ($groupByRelation === 'pep') {
                    $name = $item->pep ? 'SIM' : 'NÃO';
                } elseif ($groupByRelation === 'productRisk') {
                    // Produtos podem ter vários por avaliação
                    $name = $item->productRisk->pluck('indicatorType.description')->implode(', ');
                } else {
                    $relation = $item->$groupByRelation;
                    $name = $relation?->description ?? 'N/A';
                }

                return [
                    'name' => $name,
                    'risk_level' => $item->risk_level
                ];
            });

            // Agrupar por name e contar níveis de risco
            $summary = [];
            foreach ($dataResult as $row) {
                $n = $row['name'];
                $lvl = $row['risk_level'];

                if (!isset($summary[$n])) {
                    $summary[$n] = [
                        'name' => $n,
                        'total_baixo' => 0,
                        'total_medio' => 0,
                        'total_alto' => 0,
                        'total_geral' => 0
                    ];
                }

                switch ($lvl) {
                    case 'Baixo':
                        $summary[$n]['total_baixo']++;
                        break;
                    case 'Médio':
                        $summary[$n]['total_medio']++;
                        break;
                    case 'Alto':
                        $summary[$n]['total_alto']++;
                        break;
                }

                $summary[$n]['total_geral']++;
            }

            $finalResults = array_merge($finalResults, array_values($summary));
        }

        return !empty($finalResults) ? $finalResults : [self::DEFAULT_RESULT];
    }

    // =====================
    // MÉTODOS PÚBLICOS
    // =====================
    public function totalRiskLevelByCategory(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('category', $data);
    }

    public function totalRiskLevelByProfession(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('profession', $data);
    }

    public function totalRiskLevelByChannel(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('channel', $data, false);
    }

    public function totalRiskLevelByNationality(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('nationlity', $data);
    }

    public function totalRiskLevelByCountryResidence(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('countryResidence', $data);
    }

    public function totalRiskLevelByPep(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('pep', $data);
    }

    public function totalRiskLevelByProductRisk(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed('productRisk', $data, false);
    }

    // =====================
    // OUTROS MÉTODOS
    // =====================
    public function getDistinctYears(): array
    {
        return $this->model->selectRaw('YEAR(created_at) as ano')
            ->distinct()
            ->orderByDesc('ano')
            ->pluck('ano')
            ->toArray();
    }

    public function getMonthlyData(int $year): array
    {
        return $this->model
            ->selectRaw('MONTH(created_at) AS month, MONTHNAME(created_at) AS monthName, diligence AS name, COUNT(*) AS total')
            ->join('diligence', 'diligence.name', '=', 'risk_assessment.diligence')
            ->whereYear('created_at', $year)
            ->groupBy('month', 'monthName', 'name', 'diligence.color', 'risk_assessment.diligence')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getTotalRiskAssessments(): int
    {
        return $this->model->count();
    }

    public function getLastAssessment(int $limit = 3): ?Collection
    {
        return $this->model->with(['entity', 'user', 'productRisk'])->latest('created_at')->limit($limit)->get();
    }

    public function getByEntityId($entityId)
    {
        return $this->model->where('entity_id', $entityId)->latest('id')->first();
    }

    public function findByIndicatorType(string $indicatorType, int $idIndicator)
    {
        return $this->model->where($indicatorType, $idIndicator)->get();
    }
}
