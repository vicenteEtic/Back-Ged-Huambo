<?php

namespace App\Repositories\Entities;

use App\Enum\TypeEntity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * Summary com filtro por data
     */
    private function getRiskLevelSummary(string $groupByField, ?string $joinTable, string $nameField, array $data = []): array
    {
        $results = [
            'coletive' => [],
            'individual' => []
        ];

        $startDate = !empty($data['startDate'])
            ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
            : null;

        $endDate = !empty($data['endDate'])
            ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
            : null;

        foreach (['coletive' => TypeEntity::COLECTIVA, 'individual' => TypeEntity::SINGULAR] as $key => $type) {

            $query = $this->model
                ->join('entities', 'risk_assessment.entity_id', '=', 'entities.id')
                ->where('entities.entity_type', $type)
                ->whereNotNull('risk_assessment.risk_level');

            if ($startDate && $endDate) {
                $query->whereBetween('risk_assessment.created_at', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('risk_assessment.created_at', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('risk_assessment.created_at', '<=', $endDate);
            }

            if ($groupByField === 'product_id') {
                $query->join('product_risk', 'product_risk.risk_assessment_id', '=', 'risk_assessment.id');
                $query->join('indicator_type', 'indicator_type.id', '=', "product_risk.$groupByField");
            } elseif ($joinTable) {
                $query->join('indicator_type', 'indicator_type.id', '=', "risk_assessment.$groupByField");
            }

            $select = [
                DB::raw("$nameField AS name"),
                DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Baixo' THEN 1 ELSE 0 END) AS total_baixo"),
                DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Médio' THEN 1 ELSE 0 END) AS total_medio"),
                DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Alto' THEN 1 ELSE 0 END) AS total_alto"),
                // Total correto como soma das colunas
                DB::raw("
                    SUM(CASE WHEN risk_assessment.risk_level = 'Baixo' THEN 1 ELSE 0 END) +
                    SUM(CASE WHEN risk_assessment.risk_level = 'Médio' THEN 1 ELSE 0 END) +
                    SUM(CASE WHEN risk_assessment.risk_level = 'Alto' THEN 1 ELSE 0 END) AS total_geral
                ")
            ];

            $groupBy = match (true) {
                $groupByField === 'product_id', $joinTable => ['indicator_type.description'],
                default => ["risk_assessment.$groupByField"]
            };

            $dataResult = $query->select($select)->groupBy($groupBy)->get();
            $results[$key] = $this->formatResults($dataResult);
        }

        return $results;
    }

    /**
     * Summary sem considerar entity_type (ex: product risk geral)
     */
    private function getRiskLevelSummaryWithOutEntityType(string $groupByField, ?string $joinTable, string $nameField, array $data = []): array
    {
        $startDate = !empty($data['startDate'])
            ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
            : null;

        $endDate = !empty($data['endDate'])
            ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
            : null;

        $query = $this->model
            ->join('entities', 'risk_assessment.entity_id', '=', 'entities.id')
            ->whereNotNull('risk_assessment.risk_level');

        if ($startDate && $endDate) {
            $query->whereBetween('risk_assessment.created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('risk_assessment.created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('risk_assessment.created_at', '<=', $endDate);
        }

        if ($groupByField === 'product_id') {
            $query->join('product_risk', 'product_risk.risk_assessment_id', '=', 'risk_assessment.id');
            $query->join('indicator_type', 'indicator_type.id', '=', "product_risk.$groupByField");
        } elseif ($joinTable) {
            $query->join('indicator_type', 'indicator_type.id', '=', "risk_assessment.$groupByField");
        }

        $select = [
            DB::raw("$nameField AS name"),
            DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Baixo' THEN 1 ELSE 0 END) AS total_baixo"),
            DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Médio' THEN 1 ELSE 0 END) AS total_medio"),
            DB::raw("SUM(CASE WHEN risk_assessment.risk_level = 'Alto' THEN 1 ELSE 0 END) AS total_alto"),
            DB::raw("
                SUM(CASE WHEN risk_assessment.risk_level = 'Baixo' THEN 1 ELSE 0 END) +
                SUM(CASE WHEN risk_assessment.risk_level = 'Médio' THEN 1 ELSE 0 END) +
                SUM(CASE WHEN risk_assessment.risk_level = 'Alto' THEN 1 ELSE 0 END) AS total_geral
            ")
        ];

        $groupBy = match (true) {
            $joinTable && $nameField === 'indicator_type.description', $groupByField === 'product_id' => ['indicator_type.description'],
            !$joinTable && $nameField === '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)' => ['risk_assessment.pep'],
            default => ["risk_assessment.$groupByField"]
        };

        $dataResult = $query->select($select)->groupBy($groupBy)->get();

        return $this->formatResults($dataResult);
    }

    /**
     * Format results e remove totais zerados
     */
    private function formatResults(Collection $data): array
    {
        if ($data->isEmpty()) {
            return [];
        }

        return $data
            ->map(fn($item) => [
                'name' => $item->name ?? self::DEFAULT_RESULT['name'],
                'total_baixo' => (int) ($item->total_baixo ?? 0),
                'total_medio' => (int) ($item->total_medio ?? 0),
                'total_alto' => (int) ($item->total_alto ?? 0),
                'total_geral' => (int) ($item->total_geral ?? 0),
            ])
            ->filter(fn($item) => $item['total_geral'] > 0)
            ->values()
            ->toArray();
    }

    // =====================
    // MÉTODOS PÚBLICOS TOTAL RISK LEVEL
    // =====================

    public function totalRiskLevelByCategory(array $data = []): array
    {
        return $this->getRiskLevelSummary('categoryP', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByProfession(array $data = []): array
    {
        return $this->getRiskLevelSummary('professionP', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByChannel(array $data = []): array
    {
        return $this->getRiskLevelSummaryWithOutEntityType('channel', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByNationality(array $data = []): array
    {
        return $this->getRiskLevelSummary('nationality', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByPep(array $data = []): array
    {
        return $this->getRiskLevelSummary('pep', null, '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)', $data);
    }

    public function totalRiskLevelByCountryResidence(array $data = []): array
    {
        return $this->getRiskLevelSummary('country_residence', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByProductRisk(array $data = []): array
    {
        return $this->getRiskLevelSummaryWithOutEntityType('product_id', null, 'indicator_type.description', $data);
    }

    // =====================
    // OUTROS MÉTODOS EXISTENTES
    // =====================

    public function getDistinctYears(): array
    {
        return $this->model->select(DB::raw('YEAR(created_at) as ano'))
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();
    }

    public function getMonthlyData(int $year): array
    {
        $monthlyData = $this->model
            ->select(
                DB::raw('MONTH(risk_assessment.created_at) AS month'),
                DB::raw('MONTHNAME(risk_assessment.created_at) AS monthName'),
                DB::raw('risk_assessment.diligence AS name'),
                DB::raw('COUNT(*) AS total'),
                'diligence.color'
            )
            ->join('diligence', 'diligence.name', '=', 'risk_assessment.diligence')
            ->whereYear('risk_assessment.created_at', $year)
            ->groupBy('month', 'monthName', 'name', 'diligence.color', 'risk_assessment.diligence')
            ->orderBy('month')
            ->get()
            ->toArray();

        return $monthlyData;
    }

    public function getTotalRiskAssessments(array $data = []): int
    {
        $startDate = !empty($data['startDate'])
            ? Carbon::parse($data['startDate'])->startOfDay()
            : null;
    
        $endDate = !empty($data['endDate'])
            ? Carbon::parse($data['endDate'])->endOfDay()
            : null;
    
        $query = $this->model->newQuery(); // 🔥 evita herdar lixo
    
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    
        return $query->count();
    }

    public function getLastAssessment(int $limit = 3): ?Collection
    {
        return $this->model
            ->with(['entity', 'user', 'productRisk'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getByEntityId($entityId)
    {
        return $this->model
            ->where('entity_id', $entityId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function findByIndicatorType(string $indicatorType, int $idIndicator)
    {
        return $this->model
            ->where($indicatorType, $idIndicator)
            ->get();
    }
}
