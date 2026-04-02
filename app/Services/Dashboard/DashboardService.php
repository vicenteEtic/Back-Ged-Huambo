<?php

namespace App\Services\Dashboard;

use App\Enum\TypeEntity;
use App\Services\Entities\EntitiesService;
use App\Services\Entities\RiskAssessmentService;

class DashboardService
{
    public function __construct(
        private readonly EntitiesService $entitiesService,
        private readonly RiskAssessmentService $riskAssessmentService
    ) {}

    public function totalGeralData(array $data = [])
    {
        $totalEntities = $this->entitiesService->getTotalEntites($data);
        $totalRiskAssessments = $this->riskAssessmentService->getTotalRiskAssessments($data);
        $totalEntitiesColectivo = $this->entitiesService->getEntitiesByType(TypeEntity::COLECTIVA,$data);
        $totalEntitiesSingular = $this->entitiesService->getEntitiesByType(TypeEntity::SINGULAR,$data);
        $lastsAssessment = $this->riskAssessmentService->getLastAssessment(3);

        
        $collective_evaluation = $this->entitiesService->collectiveEntities_evaluation($data);
        $private_evaluation = $this->entitiesService->privateEntities_evaluation($data);
        return [
            'total_entities' => $totalEntities,
            'total_risk_assessments' => $totalRiskAssessments,
            'total_entities_colective' => $totalEntitiesColectivo,
            'total_entities_singular' => $totalEntitiesSingular,
            'lasts_assessment' => $lastsAssessment,
            'lasts_entities' => $this->entitiesService->getLastEntities(3),
            'private_evaluation'=> $private_evaluation,
            'collective_evaluation'=> $collective_evaluation,
        ];
    }
}
