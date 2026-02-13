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

    // =====================
    // MÉTODO CENTRAL DE ESTATÍSTICAS (corrigido)
    // =====================
  private function getRiskLevelSummaryFixed(
    string $groupByField,
    ?string $joinTable,
    string $nameField,
    array $data = [],
    bool $filterByEntityType = true
): array {
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'])->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'])->endOfDay()
        : null;

    // Define os tipos de entidade ou geral
    $entityTypes = $filterByEntityType
        ? [TypeEntity::COLECTIVA, TypeEntity::SINGULAR]
        : [null];

    $finalResults = [];

    foreach ($entityTypes as $type) {

        $subQuery = $this->model
            ->select(
                'risk_assessment.id',
                'risk_assessment.risk_level',
                DB::raw("$nameField AS name")
            )
            ->join('entities', 'entities.id', '=', 'risk_assessment.entity_id');

        if ($type !== null) {
            $subQuery->where('entities.entity_type', $type);
        }

        if ($startDate && $endDate) {
            $subQuery->whereBetween('risk_assessment.created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $subQuery->where('risk_assessment.created_at', '>=', $startDate);
        } elseif ($endDate) {
            $subQuery->where('risk_assessment.created_at', '<=', $endDate);
        }

        // JOINs adicionais
        if ($groupByField === 'product_id') {
            $subQuery
                ->join('product_risk', 'product_risk.risk_assessment_id', '=', 'risk_assessment.id')
                ->join('indicator_type', 'indicator_type.id', '=', 'product_risk.product_id');
        } elseif ($joinTable) {
            $subQuery
                ->join('indicator_type', 'indicator_type.id', '=', "risk_assessment.$groupByField");
        }

        $subQuery->groupBy('risk_assessment.id', 'risk_assessment.risk_level', 'name');

        // QUERY FINAL: somando apenas risk_level não nulos
        $query = DB::query()
            ->fromSub($subQuery, 't')
            ->whereNotNull('t.risk_level')
            ->select(
                'name',
                DB::raw("SUM(t.risk_level = 'Baixo') AS total_baixo"),
                DB::raw("SUM(t.risk_level = 'Médio') AS total_medio"),
                DB::raw("SUM(t.risk_level = 'Alto') AS total_alto"),
                DB::raw("(SUM(t.risk_level = 'Baixo') + SUM(t.risk_level = 'Médio') + SUM(t.risk_level = 'Alto')) AS total_geral")
            )
            ->groupBy('name')
            ->orderBy('name');

        $dataResult = $query->get();

        // Formata resultados e filtra itens com total_geral = 0
        $filtered = array_filter(
            $this->formatResults($dataResult),
            fn($item) => $item['total_geral'] > 0
        );

        // Unifica resultados de todos os tipos de entidade em um array único
        $finalResults = array_merge($finalResults, $filtered);
    }

    // Se estiver vazio, retorna resultado padrão
    return !empty($finalResults) ? $finalResults : [self::DEFAULT_RESULT];
}



    // =====================
    // FORMATAR RESULTADOS
    // =====================
    private function formatResults(Collection $data): array
    {
        if ($data->isEmpty()) {
            return [self::DEFAULT_RESULT];
        }

        return $data->map(fn($item) => [
            'name' => $item->name ?? self::DEFAULT_RESULT['name'],
            'total_baixo' => (int) ($item->total_baixo ?? 0),
            'total_medio' => (int) ($item->total_medio ?? 0),
            'total_alto' => (int) ($item->total_alto ?? 0),
            'total_geral' => (int) ($item->total_geral ?? 0)
        ])->toArray();
    }

    // =====================
    // MÉTODOS PÚBLICOS TOTAL RISK LEVEL
    // =====================
    public function totalRiskLevelByCategory(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'category',
            'indicator_type',
            'indicator_type.description',
            $data
        );
    }

    public function totalRiskLevelByProfession(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'profession',
            'indicator_type',
            'indicator_type.description',
            $data
        );
    }

    public function totalRiskLevelByChannel(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'channel',
            'indicator_type',
            'indicator_type.description',
            $data,
            false
        );
    }

    public function totalRiskLevelByNationality(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'nationality',
            'indicator_type',
            'indicator_type.description',
            $data
        );
    }

    public function totalRiskLevelByPep(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'pep',
            null,
            '(CASE WHEN risk_assessment.pep = 1 THEN "SIM" ELSE "NÃO" END)',
            $data
        );
    }

    public function totalRiskLevelByCountryResidence(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'country_residence',
            'indicator_type',
            'indicator_type.description',
            $data
        );
    }

    public function totalRiskLevelByProductRisk(array $data = []): array
    {
        return $this->getRiskLevelSummaryFixed(
            'product_id',
            null,
            'indicator_type.description',
            $data,
            false
        );
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
        return $this->model
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
