<?php

namespace App\Services\Entities;

use App\Jobs\GenerateAlertBeneficialOwnerJob;
use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Services\AbstractService;

class BeneficialOwnerService extends AbstractService
{
    public function __construct(BeneficialOwnerRepository $repository, private readonly PepService $pepService)
    {
        parent::__construct($repository);
    }

    public function createBeneficialOwner(array $data, int $riskAssessmentId,$entity_id): void
    {
        foreach ($data['beneficial_owners'] as $owner) {
            $owner['risk_assessment_id'] = $riskAssessmentId;
           $beneficialOwer= $this->storeOrUpdate($owner, $owner);

              GenerateAlertBeneficialOwnerJob::dispatch(  $beneficialOwer->id, $entity_id);
                
            if ($owner['pep']) {
                $pepData = [
                    "name" => $owner['name'] ?? null,
                ];
    
                $this->pepService->storeOrUpdate($pepData, $pepData);
              
            }
        }
    }
}
