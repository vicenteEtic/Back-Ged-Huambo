<?php

namespace App\Repositories\Entities;

use App\Enum\TypeEntity;
use App\Models\Alert\Alert;
use App\Models\Entities\Entities;
use App\Models\Entities\RiskAssessment;
use App\Repositories\AbstractRepository;
use App\Repositories\Indicator\IndicatorTypeRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EntitiesRepository extends AbstractRepository
{
    public $riskAssessment;
    public $indicatorType;


    public function __construct(Entities $model, RiskAssessmentRepository  $riskAssessment, IndicatorTypeRepository $indicatorType)
    {
        $this->riskAssessment = $riskAssessment;
        $this->indicatorType = $indicatorType;
        parent::__construct($model);
    }

 


    public function getTotalEntities(array $data = []): int
{
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
        : null;

        $query = $this->model->newQuery(); 

    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    return $query->count();
}

public function getEntitiesByType(TypeEntity $type, array $data = []): int
{
    $startDate = !empty($data['startDate'])
        ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
        : null;

    $endDate = !empty($data['endDate'])
        ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
        : null;

    $query = $this->model->where('entity_type', $type);

    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('created_at', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }

    return $query->count();
}


    public function getLastEntities(int $limit = 3): ?Collection
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }



    public function show(int|string $id, array $relationships = [])
    {
        $entityModel = $this->model::find($id);
    
        if (!$entityModel) {
            return null; // ou lançar exceção se preferir
        }
    
        // Contagem de alertas
        $alertsCount = Alert::where('entity_id', $id)->count();
    
        // Pega a última avaliação de risco
        $riskAssessment = $this->riskAssessment->model::where('entity_id', $id)
            ->latest('id')
            ->first();
    
        // Carrega relações apenas se existir avaliação
        if ($riskAssessment) {
            $riskAssessment->load([
                'entity:id,id,social_denomination,entity_type,customer_number,policy_number,nif,reassessmentPeriod,risk_level,diligence,last_evaluation,created_at,color',
                'user:id,first_name,last_name,email',
                'profession:id,description',
                'indetificationCapacity:id,description',
                'channel:id,description',
                'countryResidence:id,description',
                'category:id,description',
                'nationlity:id,description',
                'beneficialOwners:id,name,pep,nationality,percentage,is_legal_representative,santion,risk_assessment_id',
                'productRisk:id,product_id,score,risk_assessment_id',
                'productRisk.product:id,description,score,risk',
                'riskFormula:id,name,identification_capacity,category,profession,product_risk',
                'beneficial:id,name,nationality,is_pep,is_sanctioned,processesReportedAuthoritie,risk_assessment_id'
            ]);
        }
    
        // Monta o array final garantindo que todas as relações existam
        $entity = [
            'id' => $entityModel->id,
            'social_denomination' => $entityModel->social_denomination,
            'entity_type' => $entityModel->entity_type,
            'customer_number' => $entityModel->customer_number,
            'policy_number' => $entityModel->policy_number,
            'nif' => $entityModel->nif,
            'reassessmentPeriod' => $entityModel->reassessmentPeriod,
    
            // Relacionamentos
            'identification_capacity' => $riskAssessment ? optional($riskAssessment->indetificationCapacity)->description : null,
            'form_establishment' => $riskAssessment
                ? ($riskAssessment->form_establishment instanceof \App\Enum\FormEstablishment
                    ? $riskAssessment->form_establishment->value
                    : ($riskAssessment->form_establishment == 0 ? 'Presencial' : 'Não Presencial'))
                : null,
            'category' => $riskAssessment ? optional($riskAssessment->category)->description : null,
            'status_residence' => $riskAssessment
                ? ($riskAssessment->status_residence instanceof \App\Enum\StatusResidence
                    ? $riskAssessment->status_residence->value
                    : ($riskAssessment->status_residence == 0 ? 'Residente' : 'Não Residente'))
                : null,
            'profession' => $riskAssessment ? optional($riskAssessment->profession)->description : null,
            'pep' => $riskAssessment ? (bool) $riskAssessment->pep : false,
            'product_risk' => $riskAssessment?->productRisk ?? null,
            'country_residence' => $riskAssessment ? optional($riskAssessment->countryResidence)->description : null,
            'nationality' => $riskAssessment ? optional($riskAssessment->nationlity)->description : null,
            'punctuation' => $riskAssessment?->score ?? null,
            'risk_level' => $entityModel->risk_level,
            'diligence' => $entityModel->diligence,
            'last_evaluation' => $entityModel->last_evaluation,
            'created_at' => $entityModel->created_at,
            'color' => $entityModel->color,
            'alerts_count' => $alertsCount,
    
            // Relações completas para frontend
            'beneficial_owners' => $riskAssessment?->beneficialOwners ?? [],
            'products' => $riskAssessment?->productRisk ?? [],
            'beneficiaries' => $riskAssessment?->beneficial ?? [],
            'risk_formula' => $riskAssessment?->riskFormula ?? null
        ];
    
        return $entity;
    }



    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {

        $orderByParams = [
            'social_denomination' => 'asc'
        ];
        $query = $this->buildQuery(
            $paginate,
            $filterParams,
            $orderByParams,
            $relationships,
            ['alerts']
        );

        // Se $paginate for null, $query já é uma Collection.
        // Se $paginate tiver valor, $query é um Paginator.
        return $query;
    }


    public function privateEntities_evaluation(array $data = []): int
    {
        $startDate = !empty($data['startDate'])
            ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
            : null;
    
        $endDate = !empty($data['endDate'])
            ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
            : null;
    
        $query = RiskAssessment::whereHas('entity', function ($query) {
            $query->where('entity_type', 2);
        });
    
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    
        return $query->count();
    }

    public function collectiveEntities_evaluation(array $data = []): int
    {
        $startDate = !empty($data['startDate'])
            ? Carbon::parse($data['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->startOfDay()
            : null;
    
        $endDate = !empty($data['endDate'])
            ? Carbon::parse($data['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->endOfDay()
            : null;
    
        $query = RiskAssessment::whereHas('entity', function ($query) {
            $query->where('entity_type', 1);
        });
    
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    
        return $query->count();
    }


   
    public function profileSegmentation(): array
    {
        $riskLevels = [
            'Baixo' => 'low',
            'Médio' => 'medium',
            'Alto' => 'high',
            'Inaceitável' => 'unacceptable'
        ];
    
        $result = [];
    
        // Pré-carregar contadores para evitar múltiplas consultas
        $riskLevelCounts = $this->countByRiskLevel();
        $singleEntitiesCounts = $this->countByRiskEntity(2);
        $collectiveEntitiesCounts = $this->countByRiskEntity(1);
        $alertCounts = $this->countAlertsByLevel();
    
        foreach ($riskLevels as $levelLabel => $levelName) {
            $result[] = [
                'risk_level' => [
                    'name' => $levelName,
                    'number_of_profiles' => $riskLevelCounts[$levelLabel] ?? 0,
                    'single_entities' => $singleEntitiesCounts[$levelLabel] ?? 0,
                    'collective_entities' => $collectiveEntitiesCounts[$levelLabel] ?? 0,
                    'total_alerts_generated' => $alertCounts[$levelLabel] ?? 0,
                ]
            ];
        }
    
        return $result;
    }
    
    public function countByRiskLevel(): array
    {
        $riskLevels = ['Baixo', 'Médio', 'Alto', 'Inaceitável'];
        $result = [];
    
        foreach ($riskLevels as $level) {
            $result[$level] = $this->model->where('risk_level', $level)->count();
        }
    
        return $result;
    }
    
    public function countByRiskEntity(int $entityType): array
    {
        $riskLevels = ['Baixo', 'Médio', 'Alto', 'Inaceitável'];
        $result = [];
    
        foreach ($riskLevels as $level) {
            $result[$level] = $this->model
                ->where('risk_level', $level)
                ->where('entity_type', $entityType)
                ->count();
        }
    
        return $result;
    }
    
    // Novo método para contar alertas por nível
    public function countAlertsByLevel(): array
    {
        $riskLevels = ['Baixo', 'Médio', 'Alto', 'Inaceitável'];
        $result = [];
    
        foreach ($riskLevels as $level) {
            $result[$level] = Alert::where('level', $level)->count();
        }
    
        return $result;
    }
}
