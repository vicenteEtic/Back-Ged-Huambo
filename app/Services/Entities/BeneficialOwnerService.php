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
            $this->repository->store($owner);
          
            if ($owner['pep']) {
                $this->pepService->storeOrUpdate([
                    "name" => $owner['name'],
                ], $owner);
            }
        }
    }
}
