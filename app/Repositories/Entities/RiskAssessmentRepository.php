<?php

namespace App\Repositories\Entities;

use App\Enum\TypeEntity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Entities\RiskAssessment;
use App\Repositories\AbstractRepository;

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

      public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        // Carrega os relationships normais primeiro
        $query = $this->model->with($relationships);

        // Sobrescreve carregamento de category com campos selecionados
        $query->with([
            'category' => function ($q) {
                $q->select( 'id','score', 'description');
            },
        ]);

        // aplica filtros e ordenação usando os métodos do AbstractRepository
        $query = $this->applyFilter($query, $filterParams);
        $query = $this->applyOrder($query, $orderByParams);

        // usa mesma paginação do AbstractRepository
        return $this->paginateQuery($query, $paginate, $filterParams);
    }

    /**
     * Generate risk level summary by entity type and grouping field
     *
     * @param string $groupByField Field to group by
     * @param string|null $joinTable Table to join for description (null for PEP)
     * @param string $nameField SQL expression for name column
     * @return array
     */
    private function getRiskLevelSummary(string $groupByField, ?string $joinTable, string $nameField): array
    {
        $results = [
            'coletive' => [],
            'individual' => []
        ];

        foreach (['coletive' => TypeEntity::COLECTIVA, 'individual' => TypeEntity::SINGULAR] as $key => $type) {
            $query = $this->model
                ->join('entities', 'risk_assessment.entity_id', '=', 'entities.id')
                ->where('entities.entity_type', $type);

            if ($groupByField === 'product_id') {
                $query->join('product_risk', 'product_risk.risk_assessment_id', '=', 'risk_assessment.id');
                $query->join('indicator_type', 'indicator_type.id', '=', "product_risk.$groupByField");
            }

            if ($joinTable) {
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



            if ($joinTable && $nameField === 'indicator_type.description') {
                $groupBy[] = 'indicator_type.description';
            } elseif (!$joinTable && $nameField === '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)') {
                $groupBy[] = 'risk_assessment.pep';
            } elseif ($groupByField === 'product_id') {
                $groupBy[] = 'indicator_type.description';
            } else {
                $groupBy = ["risk_assessment.$groupByField"];
            }

            $data = $query
                ->select($select)
                ->groupBy($groupBy)
                ->get();

            $results[$key] = $this->formatResults($data);
        }

        return $results;
    }

    private function getRiskLevelSummaryWithOutEntityType(string $groupByField, ?string $joinTable, string $nameField): array
    {

        $query = $this->model
            ->join('entities', 'risk_assessment.entity_id', '=', 'entities.id');

        if ($groupByField === 'product_id') {
            $query->join('product_risk', 'product_risk.risk_assessment_id', '=', 'risk_assessment.id');
            $query->join('indicator_type', 'indicator_type.id', '=', "product_risk.$groupByField");
        }

        if ($joinTable) {
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



        if ($joinTable && $nameField === 'indicator_type.description') {
            $groupBy[] = 'indicator_type.description';
        } elseif (!$joinTable && $nameField === '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)') {
            $groupBy[] = 'risk_assessment.pep';
        } elseif ($groupByField === 'product_id') {
            $groupBy[] = 'indicator_type.description';
        } else {
            $groupBy = ["risk_assessment.$groupByField"];
        }

        $data = $query
            ->select($select)
            ->groupBy($groupBy)
            ->get()->toArray();

        return $data;
    }

    /**
     * Format query results into standardized array structure
     *
     * @param Collection $data Query results
     * @return array
     */
    private function formatResults(Collection $data): array
    {
        if ($data->isEmpty()) {
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

    public function totalRiskLevelByProductRisk(): array
    {
        return $this->getRiskLevelSummaryWithOutEntityType(
            'product_id',
            null,
            'indicator_type.description'
        );
    }

    public function totalRiskLevelByCategory(): array
    {
        return $this->getRiskLevelSummary(
            'category',
            'indicator_type',
            'indicator_type.description'
        );
    }

    public function totalRiskLevelByProfession(): array
    {
        return $this->getRiskLevelSummary(
            'profession',
            'indicator_type',
            'indicator_type.description'
        );
    }

    public function totalRiskLevelByChannel(): array
    {
        return $this->getRiskLevelSummaryWithOutEntityType(
            'channel',
            'indicator_type',
            'indicator_type.description'
        );
    }

    public function totalRiskLevelByNationality(): array
    {
        return $this->getRiskLevelSummary(
            'nationality',
            'indicator_type',
            'indicator_type.description'
        );
    }

    public function totalRiskLevelByPep(): array
    {
        return $this->getRiskLevelSummary(
            'pep',
            null,
            '(CASE WHEN pep = 1 THEN "SIM" ELSE "NÃO" END)'
        );
    }

    public function totalRiskLevelByCountryResidence(): array
    {
        return $this->getRiskLevelSummary(
            'country_residence',
            'indicator_type',
            'indicator_type.description'
        );
    }


    public  function getDistinctYears(): array
    {
        return $this->model->select(DB::raw('YEAR(created_at) as ano'))
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();
    }

    public  function getMonthlyData(int $year): array
    {
        $monthlyData = $this->model
            ->select(
                DB::raw('MONTH(risk_assessment.created_at) AS month'),
                DB::raw('MONTHNAME(risk_assessment.created_at) AS monthName'),
                DB::raw('risk_assessment.diligence AS name'),
                DB::raw('COUNT(*) AS total'),
                'diligence.color'  // Supondo que a cor esteja na tabela 'diligences'
            )
            ->join('diligence', 'diligence.name', '=', 'risk_assessment.diligence') // Adiciona o JOIN com a tabela de diligências
            ->whereYear('risk_assessment.created_at', $year)
            ->groupBy('month', 'monthName', 'name', 'diligence.color', 'risk_assessment.diligence')  // Agora estamos agrupando pela cor também
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
