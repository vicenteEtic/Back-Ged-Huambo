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

        $startDate = !empty($data['startDate']) ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay() : null;
        $endDate   = !empty($data['endDate']) ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay() : null;

        foreach (['coletive' => TypeEntity::COLECTIVA, 'individual' => TypeEntity::SINGULAR] as $key => $type) {

            $query = $this->model
                ->join('entities', 'risk_assessment.entity_id', '=', 'entities.id')
                ->where('entities.entity_type', $type);

            // Aplicar filtro de data
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

            $select = array_merge(
                [DB::raw("$nameField AS name")],
                array_map(
                    fn($level, $field) => DB::raw("SUM(CASE WHEN risk_assessment.risk_level = '$level' THEN 1 ELSE 0 END) AS $field"),
                    array_keys(self::RISK_LEVELS),
                    self::RISK_LEVELS
                ),
                [DB::raw('COUNT(*) AS total_geral')]
            );

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
    private function formatResults(Collection $data): array
    {
        if ($data->isEmpty()) {
            // Retorna a estrutura padrão zerada
            return [self::DEFAULT_RESULT];
        }

        return $data->map(fn($item) => [
            'name' => $item->name ?? self::DEFAULT_RESULT['name'],
            'total_baixo' => $item->total_baixo ?? 0,
            'total_medio' => $item->total_medio ?? 0,
            'total_alto' => $item->total_alto ?? 0,
            'total_geral' => $item->total_geral ?? 0
        ])->toArray();
    }

    private function getRiskLevelSummaryWithOutEntityType(string $groupByField, ?string $joinTable, string $nameField, array $data = []): array
    {
        $startDate = !empty($data['startDate']) ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay() : null;
        $endDate   = !empty($data['endDate']) ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay() : null;

        $query = $this->model->join('entities', 'risk_assessment.entity_id', '=', 'entities.id');

        // Filtro de data
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

        $select = array_merge(
            [DB::raw("$nameField AS name")],
            array_map(
                fn($level, $field) => DB::raw("SUM(CASE WHEN risk_assessment.risk_level = '$level' THEN 1 ELSE 0 END) AS $field"),
                array_keys(self::RISK_LEVELS),
                self::RISK_LEVELS
            ),
            [DB::raw('COUNT(*) AS total_geral')]
        );

        $groupBy = match (true) {
            $joinTable && $nameField === 'indicator_type.description', $groupByField === 'product_id' => ['indicator_type.description'],
            !$joinTable && $nameField === '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)' => ['risk_assessment.pep'],
            default => ["risk_assessment.$groupByField"]
        };

        $dataResult = $query->select($select)->groupBy($groupBy)->get();

        // Sempre retornar a mesma estrutura, mesmo se vazio
        return $this->formatResults($dataResult);
    }


    // =====================
    // MÉTODOS PÚBLICOS TOTAL RISK LEVEL
    // =====================

    public function totalRiskLevelByCategory(array $data = []): array
    {
        return $this->getRiskLevelSummary('category', 'indicator_type', 'indicator_type.description', $data);
    }

    public function totalRiskLevelByProfession(array $data = []): array
    {
        return $this->getRiskLevelSummary('profession', 'indicator_type', 'indicator_type.description', $data);
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

    public function getTotalRiskAssessments(): int
    {
        return $this->model->count();
    }

    public function getLastAssessment(int $limit = 3): ?Collection
    {
        return $this->model
            ->with(['entity', 'user', 'productRisk'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
