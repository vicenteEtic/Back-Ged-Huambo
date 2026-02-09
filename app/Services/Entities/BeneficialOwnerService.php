<?php

namespace App\Services\Entities;

use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Services\AbstractService;

class BeneficialOwnerService extends AbstractService
{
    public function __construct(BeneficialOwnerRepository $repository, private readonly PepService $pepService)
    {
        parent::__construct($repository);
    }

    public function createBeneficialOwner(array $data, int $riskAssessmentId): void
    {
        foreach ($data['beneficial_owners'] as $owner) {
            $owner['risk_assessment_id'] = $riskAssessmentId;

            $this->storeOrUpdate($owner, $owner);

        
            if ($owner['pep']) {
                $pepData = [
                    "name" => $owner['name'] ?? null,
                    "pep" => $owner['pep'],
                    "santion" => $owner['santion'] ?? null,
                    "percentage" => $owner['percentage'] ?? null,
                    "is_legal_representative" => $owner['is_legal_representative'] ?? false,
                    "nationality" => $owner['nationality'] ?? null,
                ];
    
                $this->pepService->storeOrUpdate($pepData, $pepData);
              
            }
        }
    }
}
