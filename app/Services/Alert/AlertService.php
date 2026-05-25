<?php

namespace App\Services\Alert;

use App\Services\AbstractService;
use App\Repositories\Alert\AlertRepository;
use App\Repositories\Alert\CommentAlert\CommentAlertRepository;
use App\Repositories\Entities\EntitiesRepository;
use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Services\Entities\RiskAssessmentService;

class AlertService extends AbstractService
{
    public function __construct(
        AlertRepository $repository,
        private EntitiesRepository $entitiesRepository,
        private BeneficialOwnerRepository $beneficialOwnerRepository,
        private readonly CommentAlertRepository $commentAlertRepository,
        public RiskAssessmentService $riskAssessmentService
    ) {
        parent::__construct($repository);
    }


  
    public function getTotalAlerts(array $data = []): array
    {
        return $this->repository->getTotalAlerts($data);



    
    }


    public function updateStatus(array $data, int $id)
    {
        // Atualiza o modelo
        $alert = $this->repository->updateStatus($data, $id);

        // Executa lógica adicional se for PEP
        if (!empty($data['is_pep']) && $data['is_pep'] == true) {

            return  $this->riskAssessmentService->is_pep($data, $id);
        }

         if (!empty($data['is_sanctioned']) && $data['is_sanctioned'] == true) {

            return  $this->riskAssessmentService->is_pep($data, $id);
        }

        if (!empty($data['is_reported']) && $data['is_reported'] == true) {
            return  $this->riskAssessmentService->is_pep($data, $id);
        }
        
        if (!empty($data['comment'])) {
            $commentData = [
                "alert_id" => $id,
                "comment"  => $data['comment'],
            ];

            $alert = $this->commentAlertRepository->store($commentData);
        }


        return $alert;
    }
}
